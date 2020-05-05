<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter;

use Google_Client;
use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\Call;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Assets\Video as VideoAssets;
use Minds\Core\Media\Repository as MediaRepository;
use Minds\Core\Media\YouTubeImporter\Delegates\EntityCreatorDelegate;
use Minds\Core\Media\YouTubeImporter\Delegates\QueueDelegate;
use Minds\Core\Media\YouTubeImporter\Exceptions\UnregisteredChannelException;
use Minds\Core\Media\Video\Manager as VideoManager;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Entities\EntitiesFactory;
use Minds\Entities\User;
use Minds\Entities\Video;
use Pubsubhubbub\Subscriber\Subscriber;
use Zend\Diactoros\Response\JsonResponse;

/**
 * YouTube Importer Manager
 * @package Minds\Core\Media\YouTubeImporter
 */
class Manager
{
    private const CACHE_KEY = 'youtube:token';

    // preferred qualities, in order of preference
    private const PREFERRED_QUALITIES = ['1080p', '720p', '360p', '240p', '144p'];

    /** @var Repository */
    protected $repository;

    /** @var MediaRepository */
    protected $mediaRepository;

    /** @var Google_Client */
    protected $client;

    /** @var Config */
    protected $config;

    /** @var QueueDelegate */
    protected $queueDelegate;

    /** @var EntityCreatorDelegate */
    protected $entityDelegate;

    /** @var Save */
    protected $save;

    /** @var Call */
    protected $call;

    /** @var VideoAssets */
    protected $videoAssets;

    /** @var EntitiesFactory */
    protected $entitiesBuilder;

    /** @var Subscriber */
    protected $subscriber;

    /** @var Logger */
    protected $logger;

    /** @var VideoManager */
    protected $videoManager;

    public function __construct(
        $repository = null,
        $mediaRepository = null,
        $client = null,
        $queueDelegate = null,
        $entityDelegate = null,
        $save = null,
        $call = null,
        $config = null,
        $assets = null,
        $entitiesBuilder = null,
        $subscriber = null,
        $logger = null,
        $videoManager = null
    ) {
        $this->repository = $repository ?: Di::_()->get('Media\YouTubeImporter\Repository');
        $this->mediaRepository = $mediaRepository ?: Di::_()->get('Media\Repository');
        $this->config = $config ?: Di::_()->get('Config');
        $this->queueDelegate = $queueDelegate ?: new QueueDelegate();
        $this->entityDelegate = $entityDelegate ?: new EntityCreatorDelegate();
        $this->save = $save ?: new Save();
        $this->call = $call ?: Di::_()->get('Database\Cassandra\Indexes');
        $this->client = $client ?: $this->buildClient();
        $this->videoAssets = $assets ?: new VideoAssets();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->subscriber = $subscriber ?: new Subscriber('https://pubsubhubbub.appspot.com/subscribe', $this->config->get('site_url') . 'api/v3/media/youtube-importer/hook');
        $this->logger = $logger ?: Di::_()->get('Logger');
        $this->videoManager = $videoManager ?: Di::_()->get('Media\Video\Manager');
    }

    /**
     * Connects to a channel
     * @return string
     */
    public function connect(): string
    {
        $this->configClientAuth(false);
        return $this->client->createAuthUrl();
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
        $this->configClientAuth(false);
        $this->client->fetchAccessTokenWithAuthCode($code);

        $youtube = new \Google_Service_YouTube($this->client);

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
            $this->call->insert("yt_channel:user:{$id}", [$user->getGUID()]);
        }

        $user->setYouTubeChannels($channels)
            ->save();
    }

    /**
     * @param array $opts
     * @return Response
     * @throws \Exception
     * @throws UnregisteredChannelException
     */
    public function getVideos(array $opts): Response
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => 0,
            'user_guid' => null,
            'youtube_id' => null,
            'youtube_channel_id' => null,
            'status' => null,
            'time_created' => [
                'lt' => null,
                'gt' => null,
            ],
            'statistics' => true,
            'cli' => false,
        ], $opts);

        if (!$opts['cli'] && !$this->validateChannel($opts['user'], $opts['youtube_channel_id'])) {
            throw new UnregisteredChannelException();
        }

        // if status is 'queued' or 'completed', then we don't consult youtube
        if (isset($opts['status']) && in_array($opts['status'], [TranscodeStates::QUEUED, TranscodeStates::COMPLETED], true)) {
            return $this->repository->getList($opts);
        }

        if (isset($opts['youtube_id'])) {
            $videos = $this->getYouTubeVideo($opts);
        } else {
            $videos = $this->getYouTubeVideos($opts);
        }

        /** @var YTVideo $video */
        foreach ($videos as $YTVideo) {
            // try to find it in our db
            $response = $this->repository->getList([
                'youtube_id' => $YTVideo->getVideoId(),
                'limit' => 1,
            ])->toArray();

            if (count($response) > 0) {
                /** @var Video $video */
                $video = $response[0];

                $YTVideo
                    ->setEntity($video)
                    ->setOwnerGuid($video->owner_guid)
                    ->setOwner($video->getOwnerEntity())
                    ->setStatus($video->getTranscodingStatus());

                if ($YTVideo->getStatus() === TranscodeStates::QUEUED) {
                    $transcodes = $this->videoManager->getSources($video);
                    if (count($transcodes) > 0) {
                        $YTVideo->setStatus(TranscodeStates::COMPLETED);
                        // We should not have received a completed event here, so lets resave
                        $video->setTranscodingStatus(TranscodeStates::COMPLETED);
                        $this->saveVideo($video);
                    }
                }
            }
        }

        return $videos;
    }

    /**
     * Save the video
     * @param Video $video
     * @return bool
     */
    protected function saveVideo(Video $video): bool
    {
        return $this->save
            ->setEntity($video)
            ->save(true);
    }

    /**
     * Returns videos count by transcoding_status for a user
     * @param User $user
     * @return array
     */
    public function getCount(User $user): array
    {
        return $this->repository->getCount($user);
    }

    /**
     * Initiates video import (uses Repository - queues for transcoding)
     * @param YTVideo $ytVideo
     * @throws UnregisteredChannelException
     * @throws \Exception
     */
    public function import(YTVideo $ytVideo): void
    {
        if (!$this->validateChannel($ytVideo->getOwner(), $ytVideo->getChannelId())) {
            throw new UnregisteredChannelException();
        }

        $videos = [$ytVideo];

        // if video ID is "all", we need to get all youtube videos
        if ($ytVideo->getVideoId() === 'all') {
            $videos = $this->getYouTubeVideos([
                'youtube_channel_id' => $ytVideo->getChannelId(),
                'statistics' => false,
            ]);
        }

        foreach ($videos as $video) {
            // try to find it in our db
            $response = $this->repository->getList([
                'youtube_id' => $video->getVideoId(),
                'limit' => 1,
            ])->toArray();

            // only import it if it's not been imported already
            if (count($response) === 0) {
                $video->setOwner($ytVideo->getOwner());
                $this->importVideo($video);
            }
        }
    }

    /**
     * Sends a video to a queue to be transcoded
     * @param Video $video
     */
    public function queue(Video $video): void
    {
        // send to queue so it gets downloaded
        $this->queueDelegate->onAdd($video);
    }

    /**
     * Returns maximum daily imports per user
     * @return int
     */
    public function getThreshold()
    {
        return $this->config->get('google')['youtube']['max_daily_imports'];
    }

    /**
     * Downloads a video, triggers the transcode and creates an activity.
     * Gets called by the queue runner.
     * @param Video $video
     * @throws \Minds\Exceptions\StopEventException
     */
    public function onQueue(Video $video): void
    {
        $this->logger->info("[YouTubeImporter] Downloading YouTube video ({$video->getYoutubeId()}) \n");

        // fetch the video's data and choose a format
        $ytVideo = (new YTVideo())
            ->setVideoId($video->getYoutubeId());
        $data = $this->fetchVideoData($ytVideo);

        // download the file
        $file = tmpfile();
        $path = stream_get_meta_data($file)['uri'];
        file_put_contents($path, fopen($data['format']['url'], 'r'));

        $this->logger->info("[YouTubeImporter] File saved \n");

        $media = [
            'file' => $path,
        ];

        $this->videoAssets
            ->setEntity($video)
            ->validate($media);

        $this->logger->info("[YouTubeImporter] Initiating upload to S3 ({$video->guid}) \n");

        $video->patch([
            'access_id' => 2,
        ]);
        $video->setAssets($this->videoAssets->upload($media, []));

        $this->logger->info("[YouTubeImporter] Saving video ({$video->guid}) \n");

        $success = $this->save
            ->setEntity($video)
            ->save(true);

        if (!$success) {
            throw new \Exception('Error saving video');
        }

        // create activity
        $this->entityDelegate->createActivity($video);
    }

    /**
     * Cancels a video import
     * @param User $user
     * @param string $videoId
     * @return bool
     * @throws \Exception
     */
    public function cancel(User $user, string $videoId): bool
    {
        $response = $this->repository->getList([
            'youtube_id' => $videoId,
            'limit' => 1,
        ])->toArray();

        $deleted = false;
        if (count($response) > 0 && $response[0]->getOwnerGUID() === $user->getGUID()) {
            $deleted = $this->mediaRepository->delete($response[0]->getGUID());
        }

        return $deleted;
    }

    /**
     * (Un)Subscribes from YouTube's push notifications
     * @param string $channelId
     * @param bool $subscribe
     * @return bool returns true if it succeeds
     * @throws UnregisteredChannelException
     */
    public function updateSubscription(User $user, string $channelId, bool $subscribe): bool
    {
        if (!$this->validateChannel($user, $channelId)) {
            throw new UnregisteredChannelException();
        }

        $topicUrl = "https://www.youtube.com/xml/feeds/videos.xml?channel_id={$channelId}";

        // update the channel if the value changed
        $channels = $user->getYouTubeChannels();
        $updated = false;

        foreach ($channels as $channel) {
            if ($channel['id'] === $channelId) {
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
        }

        return $updated;
    }

    /**
     * Imports a newly added YT video. This is called when the hook receives a new update.
     * @param YTVideo $video
     * @throws \IOException
     * @throws \InvalidParameterException
     */
    public function receiveNewVideo(YTVideo $video): void
    {
        // see if we have a video like this already saved
        $response = $this->repository->getList(['youtube_id' => $video->getVideoId()]);

        // if the video isn't there, we'll download it
        if ($response->count() === 0) {
            // fetch User associated with this channelId
            $result = $this->call->getRow("yt_channel:user:{$video->getChannelId()}");

            if (count($result) === 0) {
                // no User is associated with this youtube channel
                return;
            }

            /** @var User $user */
            $user = $this->entitiesBuilder->single($result[$video->getChannelId()]);

            if ($user->isBanned() || $user->getDeleted()) {
                return;
            }

            $video->setOwner($user);

            $this->import($video);
        }
    }

    /**
     * Returns an associative array :guid => :times, where :times is the amount of transcodes for that user
     * in the last 24 hours
     * @param array $ownerGuids
     * @return array
     */
    public function getOwnersEligibility(array $ownerGuids): array
    {
        return $this->repository->getOwnersEligibility($ownerGuids);
    }

    private function buildYouTubeVideoEntity($values): YTVideo
    {
        $video = new YTVideo();

        $thumbnail = $this->config->get('cdn_url') . 'api/v2/media/proxy?src=' . urlencode($values['snippet']['thumbnails']->getHigh()['url']);

        $video
            ->setVideoId($values['id'])
            ->setChannelId($values['channelId'])
            ->setThumbnail($thumbnail);

        if (isset($values['snippet'])) {
            $video
                ->setDescription($values['snippet']['description'])
                ->setTitle($values['snippet']['title'])
                ->setYoutubeCreationDate(strtotime($values['snippet']['publishedAt']));
        }

        if (isset($values['contentDetails'])) {
            $video
                ->setDuration($this->parseISO8601($values['contentDetails']['duration']));
        }

        if ($values['statistics']) {
            $video->setLikes((int) $values['statistics']['likeCount'])
                ->setDislikes((int) $values['statistics']['dislikeCount'])
                ->setFavorites((int) $values['statistics']['favoriteCount'])
                ->setViews((int) $values['statistics']['viewCount']);
        }

        return $video;
    }

    private function getYouTubeVideo(array $opts): Response
    {
        $opts = array_merge([
            'youtube_id' => null,
            'statistics' => false,
        ], $opts);

        $this->configClientAuth(true);

        $youtube = new \Google_Service_YouTube($this->client);

        $videos = new Response();

        // parts of the resource we'll query
        $parts = 'snippet,contentDetails';

        if ($opts['statistics']) {
            $parts .= ',statistics';
        }

        $response = $youtube->videos->listVideos($parts, ['id' => $opts['youtube_id']]);

        foreach ($response['items'] as $item) {
            $values = [
                'id' => $item['id'],
                'channelId' => $item['snippet']['channelId'],
                'snippet' => $item['snippet'],
                'contentDetails' => $item['contentDetails'],
            ];
            if ($opts['statistics']) {
                $values['statistics'] = $item['statistics'];
            }
            $videos[] = $this->buildYouTubeVideoEntity($values);
        }

        return $videos;
    }

    /**
     * Returns a list of YouTube videos
     * @param array $opts
     * @return Response
     * @throws \Exception
     */
    private function getYouTubeVideos(array $opts): Response
    {
        $opts = array_merge([
            'limit' => 12,
            'offset' => null,
            'user_guid' => null,
            'youtube_id' => null,
            'youtube_channel_id' => null,
            'status' => null,
            'time_created' => [
                'lt' => null,
                'gt' => null,
            ],
            'statistics' => false,
        ], $opts);

        $this->configClientAuth(true);

        $youtube = new \Google_Service_YouTube($this->client);

        // get channel
        $channelsResponse = $youtube->channels->listChannels('contentDetails', [
            'id' => $opts['youtube_channel_id'],
        ]);

        $videos = new Response();

        foreach ($channelsResponse['items'] as $channel) {
            $uploadsListId = $channel['contentDetails']['relatedPlaylists']['uploads'];

            // get videos
            $playlistOpts = [
                'playlistId' => $uploadsListId,
                'maxResults' => $opts['limit'],
            ];

            if ($opts['offset']) {
                $playlistOpts['pageToken'] = $opts['offset'];
            }

            // get playlists
            $playlistResponse = $youtube->playlistItems->listPlaylistItems('snippet', $playlistOpts);

            $videos->setPagingToken($playlistResponse->getNextPageToken());

            // get all IDs so we can do a single query call
            $videoIds = array_map(function ($item) {
                return $item['snippet']['resourceId']['videoId'];
            }, $playlistResponse['items']);

            // parts of the resource we'll query
            $parts = 'contentDetails';

            if ($opts['statistics']) {
                $parts .= ',statistics';
            }

            // get data on all returned videos
            $videoResponse = $youtube->videos->listVideos($parts, ['id' => implode(',', $videoIds)]);

            // build video entities
            foreach ($playlistResponse['items'] as $item) {
                $youtubeId = $item['snippet']['resourceId']['videoId'];

                $currentVideo = array_values(array_filter($videoResponse['items'], function (\Google_Service_YouTube_Video $item) use ($youtubeId) {
                    return $item->getId() === $youtubeId;
                }))[0];

                $values = [
                    'id' => $item['snippet']['resourceId']['videoId'],
                    'channelId' => $item['snippet']['channelId'],
                    'snippet' => $item['snippet'],
                    'contentDetails' => $currentVideo['contentDetails'],
                ];

                if ($opts['statistics']) {
                    $values['statistics'] = $currentVideo['statistics'];
                }

                $videos[] = $this->buildYouTubeVideoEntity($values);
            }
        }
        return $videos;
    }

    /**
     * Fetches the data of a YouTube Video
     * @param YTVideo $ytVideo
     * @return array
     * @throws \Exception
     */
    private function fetchVideoData(YTVideo $ytVideo): array
    {
        // get and decode the data
        parse_str(file_get_contents("https://youtube.com/get_video_info?video_id=" . $ytVideo->getVideoId()), $info);

        $videoData = json_decode($info['player_response'], true);

        // get video details
        $videoDetails = $videoData['videoDetails'];

        // get streaming formats
        $streamingDataFormats = $videoData['streamingData']['formats'];

        // validate length
        $this->videoAssets->validate(['length' => $videoDetails['lengthSeconds'] / 60]);

        // find best suitable format
        $format = [];
        $i = 0;

        $length = count(static::PREFERRED_QUALITIES);
        while (count($format) === 0 && $i < $length) {
            foreach ($streamingDataFormats as $f) {
                if ($f['qualityLabel'] === static::PREFERRED_QUALITIES[$i]) {
                    $format = $f;
                }
            }

            $i++;
        }

        return [
            'details' => $videoDetails,
            'format' => $format,
        ];
    }

    /**
     * Imports a YouTube video
     * @param YTVideo $ytVideo
     * @return void
     * @throws \Exception
     */
    private function importVideo(YTVideo $ytVideo): void
    {
        $data = $this->fetchVideoData($ytVideo);

        // create the video
        $video = new Video();

        $video->patch([
            'title' => isset($data['details']['title']) ? $data['details']['title'] : '',
            'description' => isset($data['details']['description']) ? $data['details']['description'] : '',
            'batch_guid' => 0,
            'access_id' => 0,
            'owner_guid' => $ytVideo->getOwnerGuid(),
            'container_guid' => $ytVideo->getOwnerGuid(),
            'full_hd' => $ytVideo->getOwner()->isPro(),
            'youtube_id' => $ytVideo->getVideoId(),
            'youtube_channel_id' => $ytVideo->getChannelId(),
            'transcoding_status' => TranscodeStates::QUEUED,
        ]);

        $this->saveVideo($video);

        // check if we're below the threshold
        if ($this->getOwnersEligibility([$ytVideo->getOwner()->guid])[$ytVideo->getOwner()->guid] < $this->getThreshold()) {
            $this->queue($video);
        }
    }

    /**
     * Creates new instance of Google_Client and adds client_id and secret
     * @param bool $useDevKey
     * @return Google_Client
     */
    private function buildClient(bool $useDevKey = true): Google_Client
    {
        $client = new Google_Client();

        // add scopes
        $client->addScope(\Google_Service_YouTube::YOUTUBE_READONLY);

        $client->setRedirectUri($this->config->get('site_url')
            . 'api/v3/media/youtube-importer/account/redirect');

        $client->setAccessType('offline');

        return $client;
    }

    /**
     * Configures the Google Client to either use a developer key or a client id / secret
     * @param $useDevKey
     */
    private function configClientAuth($useDevKey)
    {
        // set auth config
        if ($useDevKey) {
            $this->client->setDeveloperKey($this->config->get('google')['youtube']['api_key']);
            $this->client->setClientId('');
            $this->client->setClientSecret('');
        } else {
            $this->client->setDeveloperKey('');
            $this->client->setClientId($this->config->get('google')['youtube']['client_id']);
            $this->client->setClientSecret($this->config->get('google')['youtube']['client_secret']);
        }
    }

    /**
     * Returns whether the channel belongs to the User
     * @param User $user
     * @param string $channelId
     * @return bool
     */
    private function validateChannel(User $user, string $channelId): bool
    {
        return count(array_filter($user->getYouTubeChannels(), function ($value) use ($channelId) {
            return $value['id'] === $channelId;
        })) !== 0;
    }

    /**
     * returns duration in seconds
     * @param string $duration
     * @return int
     */
    private function parseISO8601(string $duration): int
    {
        return (new \DateTime('@0'))->add(new \DateInterval($duration))->getTimestamp();
    }
}
