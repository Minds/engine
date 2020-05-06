<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Common\Repository\Response;
use Minds\Core\Config\Config;
use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Assets\Video as VideoAssets;
use Minds\Core\Media\Repository as MediaRepository;
use Minds\Core\Media\YouTubeImporter\Delegates\EntityCreatorDelegate;
use Minds\Core\Media\YouTubeImporter\Delegates\QueueDelegate;
use Minds\Core\Media\YouTubeImporter\Exceptions\UnregisteredChannelException;
use Minds\Core\Media\YouTubeImporter\Manager;
use Minds\Core\Media\YouTubeImporter\Repository;
use Minds\Core\Media\YouTubeImporter\TranscoderBridge;
use Minds\Core\Media\YouTubeImporter\YTVideo;
use Minds\Core\Media\YouTubeImporter\YTClient;
use Minds\Core\Media\YouTubeImporter\YTApi;
use Minds\Entities\User;
use Minds\Entities\Video;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Pubsubhubbub\Subscriber\Subscriber;

class ManagerSpec extends ObjectBehavior
{
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

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Subscriber */
    protected $subscriber;

    /** @var Logger */
    protected $logger;

    /** @var TranscoderBridge */
    protected $transcoderBridge;

    public function let(
        Repository $repository,
        MediaRepository $mediaRepository,
        YTClient $ytClient,
        YTApi $ytApi,
        QueueDelegate $queueDelegate,
        EntityCreatorDelegate $entityDelegate,
        Save $save,
        Config $config,
        VideoAssets $videoAssets,
        EntitiesBuilder $entitiesBuilder,
        Logger $logger,
        TranscoderBridge $transcoderBridge
    ) {
        $this->repository = $repository;
        $this->mediaRepository = $mediaRepository;
        $this->ytClient = $ytClient;
        $this->ytApi = $ytApi;
        $this->queueDelegate = $queueDelegate;
        $this->entityDelegate = $entityDelegate;
        $this->save = $save;
        $this->config = $config;
        $this->videoAssets = $videoAssets;
        $this->logger = $logger;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->transcoderBridge = $transcoderBridge;

        $this->beConstructedWith(
            $repository,
            $mediaRepository,
            $ytClient,
            $ytApi,
            $queueDelegate,
            $entityDelegate,
            $save,
            $config,
            $videoAssets,
            $entitiesBuilder,
            $logger,
            $transcoderBridge
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_not_get_videos_if_channel_is_not_associated_to_user(Response $response, User $user)
    {
        $user->getYouTubeChannels()
            ->shouldBeCalled()
            ->willReturn([]);

        $this->shouldThrow(UnregisteredChannelException::class)->during('getVideos', [
            [
                'user' => $user,
                'status' => 'completed',
                'youtube_channel_id' => 'id123',
            ],
        ]);
    }

    public function it_should_get_completed_videos(Response $response, User $user)
    {
        $this->repository->getList(Argument::any())
            ->shouldBeCalled()
            ->willReturn($response);

        $user->getYouTubeChannels()
            ->shouldBeCalled()
            ->willReturn([
                [
                    'id' => 'id123',
                ],
            ]);

        $this->getVideos([
            'user' => $user,
            'status' => 'completed',
            'youtube_channel_id' => 'id123',
        ])
            ->shouldReturn($response);
    }

    public function it_should_get_a_count_of_videos(User $user)
    {
        $this->repository->getCount($user)
            ->shouldBeCalled()
            ->willReturn([
                'queued' => 3,
                'transcoding' => 3,
            ]);

        $this->getCount($user)
            ->shouldReturn([
                'queued' => 3,
                'transcoding' => 3,
            ]);
    }


    public function it_should_get_owners_elegibility()
    {
        $this->repository->getOwnersEligibility([1, 2])
            ->shouldBeCalled()
            ->willReturn([1 => 10, 2 => 3]);

        $this->getOwnersEligibility([1, 2])->shouldReturn([1 => 10, 2 => 3]);
    }

    public function it_should_get_the_daily_threshold()
    {
        $this->config->get('google')
            ->shouldBeCalled()
            ->willReturn([
                'youtube' => [
                    'max_daily_imports' => 10,
                ],
            ]);

        $this->getThreshold()->shouldReturn(10);
    }

    public function it_should_download_and_save_on_queue()
    {
        $video = new Video();
        $video->setYouTubeId('ytId');

        $this->ytApi->getVideoInfo('ytId')
            ->willReturn([
                'videoDetails' => [],
                'streamingData' => [
                    'formats' => [
                        [
                            'itag' => 35, //Invalid, should skip
                        ],
                        [
                            'itag' => 18, // 360p
                        ],
                        [
                            'itag' => 22, // 720p
                        ],
                    ]
                ]
            ]);

        $this->transcoderBridge->addFromYouTube($video, Argument::that(function ($source) {
            return true;
        }))
            ->shouldBeCalled()
            ->willReturn(true);

        $this->save->setEntity($video)
            ->willReturn($this->save);

        $this->save->save(Argument::any())
            ->willReturn(true);

        $this->onQueue($video);
    }
}
