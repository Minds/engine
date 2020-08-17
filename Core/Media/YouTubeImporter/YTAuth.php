<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Data\Call;
use Minds\Entities\User;
use Minds\Core\Config;
use Google_Service_Exception;

class YTAuth
{
    /** @var YTClient */
    protected $ytClient;

    /** @var YTSubscription */
    protected $ytSubscription;

    /** @var Save */
    protected $save;

    /** @var Call */
    protected $db;

    /** @var Config */
    protected $config;

    public function __construct(
        $ytClient = null,
        $ytSubscription = null,
        $save = null,
        $db = null,
        $config = null
    ) {
        $this->ytClient = $ytClient ?? Di::_()->get('Media\YouTubeImporter\YTClient');
        $this->ytSubscription = $ytSubscription ?? new YTSubscription();
        $this->save = $save ?? new Save();
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Indexes');
        $this->config = $config ?? Di::_()->get('Config');
    }

    /**
     * Connects to a youtube account via a minds link in their description
     * @param User $user
     * @param string $channelId
     * @return bool
     */
    public function connectWithBacklink(User $user, string $channelId): bool
    {
        try {
            $youtube = $this->ytClient->getService(true);

            $channelsResponse = $youtube->channels->listChannels('id, snippet', [
                'id' => $channelId
            ]);
        } catch (Google_Service_Exception $e) {
            throw new QuotaExceededException();
        }

        $description = strtolower($channelsResponse->items[0]->snippet->description);

        $url = strtolower($this->config->get('site_url') . $user->username);

        if (strpos($description, $url) === false) {
            return false;
        }

        // save channel id into indexes
        $this->db->insert("yt_channel:user:{$channelId}", [$user->getGUID()]);

        $ytChannel = [
            'id' => $channelId,
            'title' => $channelsResponse->items[0]->snippet->title,
            'connected' => time(),
            'auto_import' => false, // This is handled by YTSubscription
        ];

        $user->setYouTubeChannels([ $ytChannel ]);

        $this->save
            ->setEntity($user)
            ->save();

        // TODO: consider moving to a delegate
        $this->ytSubscription->update($user, $channelId, true);

        return true;
    }

    /**
     * Connects to a channel
     * @return string
     */
    public function connect(): string
    {
        return $this->ytClient->getClient(false)->createAuthUrl();
    }

    /**
     * Disconnects a YouTube account from a User
     * @param User $user
     * @param string $channelId
     * @return void
     * @throws \Minds\Exceptions\StopEventException
     */
    public function disconnect(User $user, string $channelId): void
    {
        // filter out the particular element, if found
        $channels = array_filter($user->getYouTubeChannels(), function ($value) use ($channelId) {
            return $value['id'] !== $channelId;
        });

        $user->setYouTubeChannels($channels);

        $this->save
            ->setEntity($user)
            ->save();
    }

    /**
     * Receives the access token and save to yt_connected
     * @param User $user
     * @param string $code
     */
    public function fetchToken(User $user, string $code): void
    {
        // We use the user's access token only this time to get channel details
        $this->ytClient->getClient(false)->fetchAccessTokenWithAuthCode($code);

        $youtube = $this->ytClient->getService(false);

        $channelsResponse = $youtube->channels->listChannels('id, snippet', [
            'mine' => 'true',
        ]);

        $channels = $user->getYouTubeChannels();
        foreach ($channelsResponse['items'] as $channel) {
            // only add the channel if it's not already registered
            if (count(array_filter($channels, function ($value) use ($channel) {
                return $value['id'] === $channel['id'];
            })) === 0) {
                $channels[] = [
                    'id' => $channel['id'],
                    'title' => $channel['snippet']['title'],
                    'connected' => time(),
                    'auto_import' => false,
                ];
            }
        }

        // get channel ids
        $channelIds = array_map(function ($item) {
            return $item['id'];
        }, $channels);

        // save channel ids into indexes
        foreach ($channelIds as $id) {
            $this->db->insert("yt_channel:user:{$id}", [$user->getGUID()]);
        }

        $user->setYouTubeChannels($channels);

        $this->save
            ->setEntity($user)
            ->save();
    }
}
