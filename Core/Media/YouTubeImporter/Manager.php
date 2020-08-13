<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Media\YouTubeImporter;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Assets\Video as VideoAssets;
use Minds\Core\Media\Repository as MediaRepository;
use Minds\Core\Media\YouTubeImporter\Delegates\EntityCreatorDelegate;
use Minds\Core\Media\YouTubeImporter\Delegates\QueueDelegate;
use Minds\Core\Media\YouTubeImporter\Exceptions\UnregisteredChannelException;
use Minds\Core\Media\Video\Transcoder\TranscodeStates;
use Minds\Entities\EntitiesFactory;
use Minds\Entities\User;
use Minds\Entities\Video;
use Minds\Core\Data\Cache\PsrWrapper;
use Zend\Diactoros\Response\JsonResponse;

/**
 * YouTube Importer Manager
 * @package Minds\Core\Media\YouTubeImporter
 */
class Manager
{
    private const CACHE_KEY = 'youtube:token';

    /** @var Repository */
    protected $repository;

    /** @var MediaRepository */
    protected $mediaRepository;

    /** @var YTClient */
    protected $ytClient;

    /** @var YTApi */
    protected $ytApi;

    /** @var Config */
    protected $config;

    /** @var QueueDelegate */
    protected $queueDelegate;

    /** @var EntityCreatorDelegate */
    protected $entityDelegate;

    /** @var Save */
    protected $save;

    /** @var VideoAssets */
    protected $videoAssets;

    /** @var EntitiesFactory */
    protected $entitiesBuilder;

    /** @var Logger */
    protected $logger;

    /** @var TranscoderBridge */
    protected $transcoderBridge;

    /** @var PsrWrapper */
    protected $cache;

    public function __construct(
        $repository = null,
        $mediaRepository = null,
        $ytClient = null,
        $ytApi = null,
        $queueDelegate = null,
        $entityDelegate = null,
        $save = null,
        $config = null,
        $videoAssets = null,
        $entitiesBuilder = null,
        $logger = null,
        $transcoderBridge = null,
        $cache = null
    ) {
        $this->repository = $repository ?: Di::_()->get('Media\YouTubeImporter\Repository');
        $this->mediaRepository = $mediaRepository ?: Di::_()->get('Media\Repository');
        $this->ytClient = $ytClient ?: new YTClient();
        $this->ytApi = $ytApi ?? new YTApi();
        $this->queueDelegate = $queueDelegate ?: new QueueDelegate();
        $this->entityDelegate = $entityDelegate ?: new EntityCreatorDelegate();
        $this->save = $save ?: new Save();
        $this->config = $config ?: Di::_()->get('Config');
        $this->videoAssets = $videoAssets ?: new VideoAssets();
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
        $this->logger = $logger ?: Di::_()->get('Logger');
        $this->transcoderBridge = $transcoderBridge ?? new TranscoderBridge();
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
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
            $videos = new Response([ $this->getYouTubeVideo($opts['youtube_id'], $opts) ]);
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
            }
        }

        $videos = $videos->filter(function ($ytVideo) use ($opts) {
            if (isset($opts['youtube_id'])) {
                return true; // This allows completed status for status checks
            }
            return $ytVideo->getStatus() !== TranscodeStates::COMPLETED;
        });

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

        $ytVideos = [$ytVideo];

        // if video ID is "all", we need to get all youtube videos
        if ($ytVideo->getVideoId() === 'all') {
            $ytVideos = $this->getYouTubeVideos([
                'youtube_channel_id' => $ytVideo->getChannelId(),
                'statistics' => false,
                'limit' => 100,
            ]);
        }

        foreach ($ytVideos as $ytVideo) {
            // try to find it in our db
            $response = $this->repository->getList([
                'youtube_id' => $ytVideo->getVideoId(),
                'limit' => 1,
            ])->toArray();

            // only import it if it's not been imported already
            if (count($response) === 0) {
                $ytVideo->setOwner($ytVideo->getOwner());
                $this->importVideo($ytVideo);
            }
        }
    }

    /**
     * Returns maximum daily imports per user
     * @return int
     */
    public function getThreshold(): int
    {
        return $this->config->get('google')['youtube']['max_daily_imports'] ?? 10;
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

        /** @var YTVideoSource[] */
        $videoSources = $data['sources'];

        foreach ($videoSources as $source) {
            $this->transcoderBridge->addFromYoutube($video, $source);
            $this->logger->info("[YouTubeImporter] File saved (itag:{$source->getItag()}) \n");
        }

        $video->patch([
            'access_id' => 2,
            'transcoding_status' => TranscodeStates::COMPLETED,
        ]);

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

    /**
     * Get a youtube video
     * @param string $id
     * @return YTVideo
     */
    public function getYouTubeVideo(string $id, array $opts): ?YTVideo
    {
        $opts = array_merge([
            'statistics' => false,
        ], $opts);

        $youtube = $this->ytClient->getService(true);

        // parts of the resource we'll query
        $parts = 'snippet,contentDetails';

        if ($opts['statistics']) {
            $parts .= ',statistics';
        }

        /** @var string */
        $cacheKey = "ytimporter:videolist-id:$id";

        if ($cached = $this->cache->get($cacheKey)) {
            $response = unserialize($cached);
        } else {
            $response = $youtube->videos->listVideos($parts, ['id' => $id]);
            $this->cache->set($cacheKey, serialize($response), 3600); // 1 hour cache
        }

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
            return $this->buildYouTubeVideoEntity($values);
        }

        return null;
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

        $youtube = $this->ytClient->getService(true);

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
        $videoData = $this->ytApi->getVideoInfo($ytVideo->getVideoId());

        // get video details
        $videoDetails = $videoData['videoDetails'];

        // get streaming formats
        $sources = array_map(function ($format) {
            $source = new YTVideoSource();
            return $source->fromArray($format);
        }, $videoData['streamingData']['formats']);

        $availableSources = array_filter($sources, function ($source) {
            return in_array($source->getItag(), array_keys(TranscoderBridge::YT_ITAGS_TO_PROFILES), true);
        });

        // We hack the sources to make a thumbnail
        $thumbnail = new YTVideoSource();
        $thumbnail->setItag(-1)
            ->setUrl("https://img.youtube.com/vi/{$ytVideo->getVideoId()}/maxresdefault.jpg");
        array_unshift($availableSources, $thumbnail);

        return [
            'details' => $videoDetails,
            'sources' => $availableSources,
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

        $tags = array_map(function ($keyword) {
            return str_replace(' ', '', $keyword);
        }, $data['details']['keywords'] ?? []);

        $video->patch([
            'title' => isset($data['details']['title']) ? $data['details']['title'] : '',
            'description' => isset($data['details']['shortDescription']) ? $data['details']['shortDescription'] : '',
            'batch_guid' => 0,
            'access_id' => 0,
            'owner_guid' => $ytVideo->getOwnerGuid(),
            'container_guid' => $ytVideo->getOwnerGuid(),
            // 'full_hd' => $ytVideo->getOwner()->isPro(),
            'youtube_id' => $ytVideo->getVideoId(),
            'youtube_channel_id' => $ytVideo->getChannelId(),
            'transcoding_status' => TranscodeStates::QUEUED,
            'time_created' => $ytVideo->getYoutubeCreationDate(),
            'time_sent' => $ytVideo->getYoutubeCreationDate(),
            'tags' => $tags,
        ]);

        $this->saveVideo($video);

        $this->queueDelegate->onAdd($video);
    }


    /**
     * Returns whether the channel belongs to the User
     * @param User $user
     * @param string $channelId
     * @return bool
     */
    public function validateChannel(User $user, string $channelId): bool
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
