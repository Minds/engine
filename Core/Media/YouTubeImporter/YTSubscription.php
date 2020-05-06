<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Data\Call;
use Minds\Common\Repository\Response;
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

    public function __construct(
        $ytClient = null,
        $manager = null,
        $repository = null,
        $subscriber = null,
        $config = null,
        $entitiesBuilder = null,
        $save = null,
        $db = null
    ) {
        $config = $config ?? Di::_()->get('Config');
        $this->ytClient = $ytClient ?? Di::_()->get('Media\YouTubeImporter\YTClient');
        $this->manager = $manager ?? Di::_()->get('Media\YouTubeImporter\Manager');
        $this->repository = $repository ?? Di::_()->get('Media\YouTubeImporter\Repository');
        $this->subscriber = $subscriber ?: new Subscriber('https://pubsubhubbub.appspot.com/subscribe', $config->get('site_url') . 'api/v3/media/youtube-importer/hook');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->save = $save ?? new Save();
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Indexes');
    }

    /**
     * (Un)Subscribes from YouTube's push notifications
     * @param string $channelId
     * @param bool $subscribe
     * @return bool returns true if it succeeds
     * @throws UnregisteredChannelException
     */
    public function update(User $user, string $channelId, bool $subscribe): bool
    {
        if (!$this->manager->validateChannel($user, $channelId)) {
            throw new UnregisteredChannelException();
        }

        $topicUrl = "https://www.youtube.com/xml/feeds/videos.xml?channel_id={$channelId}";

        // update the channel if the value changed
        $channels = $user->getYouTubeChannels();
        $updated = false;

        foreach ($channels as $channel) {
            if ($channel['id'] !== $channelId) {
                continue;
            }

            if ($channel['auto_import'] === $subscribe) {
                return true;
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
     * Imports a newly added YT video. This is called when the hook receives a new update.
     * @param YTVideo $ytVideo
     * @throws \IOException
     * @throws \InvalidParameterException
     */
    public function onNewVideo(YTVideo $ytVideo): void
    {
        // see if we have a video like this already saved
        $response = $this->repository->getList([
            'youtube_id' => $ytVideo->getVideoId()
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
            $user = $this->entitiesBuilder->single($result[$ytVideo->getChannelId()]);

            if ($user->isBanned() || $user->getDeleted()) {
                return;
            }

            $video->setOwner($user);

            // Import the new video
            $this->manager->import($ytVideo);
        }
    }
}
