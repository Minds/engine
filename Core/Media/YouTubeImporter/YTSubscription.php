<?php

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\YouTubeImporter\Exceptions\UnregisteredChannelException;
use Minds\Entities\User;
use Minds\Core\Security\ACL;
use Minds\Core\Security\RateLimits\KeyValueLimiter;
use Pubsubhubbub\Subscriber\Subscriber;

class YTSubscription
{
    /** @var YTClient */
    protected $ytClient;

    /** @var Manager */
    protected $manager;

    /** @var Repository */
    protected $repository;

    /** @var Subscriber */
    protected $subscriber;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Save */
    protected $save;

    /** @var Call */
    protected $db;

    /** @var ACL */
    protected $acl;

    /** @var KeyValueLimiter */
    protected $kvLimiter;

    public function __construct(
        $ytClient = null,
        $manager = null,
        $repository = null,
        $subscriber = null,
        $config = null,
        $entitiesBuilder = null,
        $save = null,
        $db = null,
        $acl = null,
        $kvLimiter = null
    ) {
        $config = $config ?? Di::_()->get('Config');
        $this->ytClient = $ytClient ?? Di::_()->get('Media\YouTubeImporter\YTClient');
        $this->manager = $manager ?? Di::_()->get('Media\YouTubeImporter\Manager');
        $this->repository = $repository ?? Di::_()->get('Media\YouTubeImporter\Repository');
        $this->subscriber = $subscriber ?: new Subscriber('https://pubsubhubbub.appspot.com/subscribe', $config->get('site_url') . 'api/v3/media/youtube-importer/hook');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->save = $save ?? new Save();
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Indexes');
        $this->acl = $acl ?? Di::_()->get('Security\ACL');
        $this->kvLimiter = $kvLimiter ?? Di::_()->get("Security\RateLimits\KeyValueLimiter");
    }

    /**
     * (Un)Subscribes from YouTube's push notifications
     * @param User $user
     * @param string $channelId
     * @param bool $subscribe
     * @return bool returns true if it succeeds
     * @throws UnregisteredChannelException
     * @throws \Minds\Exceptions\StopEventException
     */
    public function update(User $user, string $channelId, bool $subscribe): bool
    {
        if (!$this->manager->validateChannel($user, $channelId)) {
            throw new UnregisteredChannelException();
        }

        $topicUrl = $this->getTopicUrl($channelId);

        // update the channel if the value changed
        $channels = $user->getYouTubeChannels();
        $updated = false;

        foreach ($channels as $channel) {
            if ($channel['id'] !== $channelId) {
                continue;
            }

            $updated = $subscribe ? $this->subscriber->subscribe($topicUrl) !== false : $this->subscriber->unsubscribe($topicUrl) !== false;

            // if the subscription was correctly updated
            if ($updated) {
                // update and save channel
                $channel['auto_import'] = $subscribe;

                $user->updateYouTubeChannel($channel);

                $this->save
                    ->setEntity($user)
                    ->save();
            }
            break;
        }

        return $updated;
    }

    /**
     * Renew the lease for the subscription
     * @param string $channelId
     * @return bool
     */
    public function renewLease(string $channelId): bool
    {
        $topicUrl = $this->getTopicUrl($channelId);
        return $this->subscriber->subscribe($topicUrl) !== false;
    }

    /**
     * Imports a newly added YT video. This is called when the hook receives a new update.
     * @param YTVideo $ytVideo
     * @throws Exceptions\UnregisteredChannelException
     */
    public function onNewVideo(YTVideo $ytVideo): void
    {
        // Only allow one request per video pub per day
        // This is because duplicate can happen and elasticsearch can be delayed
        $this->kvLimiter
            ->setKey('yt-importer-pubsub')
            ->setValue($ytVideo->getVideoId())
            ->setSeconds(86400) // Day
            ->setMax(1) // 1 per day
            ->checkAndIncrement(); // Will throw exception

        // see if we have a video like this already saved
        $response = $this->repository->getList([
            'youtube_id' => $ytVideo->getVideoId(),
        ]);

        // if the video isn't there, we'll download it
        if ($response->count() === 0) {
            // fetch User associated with this channelId
            $result = $this->db->getRow("yt_channel:user:{$ytVideo->getChannelId()}");

            if (count($result) === 0) {
                // no User is associated with this youtube channel
                return;
            }

            /** @var User $user */
            $user = $this->entitiesBuilder->single($result[0]);

            if ($user->isBanned() || $user->getDeleted()) {
                return;
            }

            $ytVideo->setOwner($user);
            $ytVideo->setOwnerGuid($user->getGuid());

            // Bypass ACL as we are saving as another user
            $ia = $this->acl->setIgnore(true);

            // Import the new video
            $this->manager->import($ytVideo, false);

            // Re-impose previous ignore access setting
            $this->acl->setIgnore($ia);
        }
    }

    /**
     * Builds topic url from channel id
     * @param string $channelId
     * @return string
     */
    private function getTopicUrl(string $channelId): string
    {
        return "https://www.youtube.com/xml/feeds/videos.xml?channel_id={$channelId}";
    }
}
