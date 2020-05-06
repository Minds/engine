<?php
namespace Minds\Core\Media\YouTubeImporter;

use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Data\Call;
use Minds\Entities\User;

class YTAuth
{
    /** @var YTClient */
    protected $ytClient;

    /** @var Save */
    protected $save;

    /** @var Call */
    protected $db;

    public function __construct($ytClient = null, $save = null, $db = null)
    {
        $this->ytClient = $ytClient ?? Di::_()->get('Media\YouTubeImporter\YTClient');
        $this->save = $save ?? new Save();
        $this->db = $db ?: Di::_()->get('Database\Cassandra\Indexes');
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
