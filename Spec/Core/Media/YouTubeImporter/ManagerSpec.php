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
use Minds\Core\Media\YouTubeImporter\YTVideo;
use Minds\Core\Media\Video\Manager as VideoManager;
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

    /** @var \Google_Client */
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

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Subscriber */
    protected $subscriber;

    /** @var Logger */
    protected $logger;

    /** @var VideoManager */
    protected $videoManager;

    public function let(
        Repository $repository,
        MediaRepository $mediaRepository,
        \Google_Client $client,
        QueueDelegate $queueDelegate,
        EntityCreatorDelegate $entityDelegate,
        Save $save,
        Config $config,
        Call $call,
        VideoAssets $videoAssets,
        EntitiesBuilder $entitiesBuilder,
        Subscriber $subscriber,
        Logger $logger,
        VideoManager $videoManager
    ) {
        $this->repository = $repository;
        $this->mediaRepository = $mediaRepository;
        $this->config = $config;
        $this->queueDelegate = $queueDelegate;
        $this->entityDelegate = $entityDelegate;
        $this->save = $save;
        $this->call = $call;
        $this->logger = $logger;
        $this->client = $client;
        $this->videoAssets = $videoAssets;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->subscriber = $subscriber;
        $this->videoManager = $videoManager;

        $this->beConstructedWith(
            $repository,
            $mediaRepository,
            $client,
            $queueDelegate,
            $entityDelegate,
            $save,
            $call,
            $config,
            $videoAssets,
            $entitiesBuilder,
            $subscriber,
            $logger,
            $videoManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_auth_url()
    {
        $this->config->get('google')
            ->shouldBeCalled()
            ->willReturn([
                'youtube' => [
                    'client_id' => 'client_id',
                    'client_secret' => 'client_secret',
                ],
            ]);
        $this->client->setDeveloperKey('')
            ->shouldBeCalled();
        $this->client->setClientId('client_id')
            ->shouldBeCalled();
        $this->client->setClientSecret('client_secret')
            ->shouldBeCalled();
        $this->client->createAuthUrl()
            ->shouldBeCalled()
            ->willReturn('url');
        $this->connect()->shouldReturn('url');
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

    public function it_should_not_receive_a_new_video_if_it_already_exists(YTVideo $video, Video $video2, User $user)
    {
        $video->getVideoId()
            ->shouldBeCalled()
            ->willReturn('id');

        $this->repository->getList(['youtube_id' => 'id'])
            ->shouldBeCalled()
            ->willReturn(new Response([$video2]));

        $this->receiveNewVideo($video);
    }

    public function it_should_not_receive_a_new_video_if_no_user_is_associated_to_that_yt_channel(YTVideo $video, User $user)
    {
        $video->getVideoId()
            ->shouldBeCalled()
            ->willReturn('id');

        $this->repository->getList(['youtube_id' => 'id'])
            ->shouldBeCalled()
            ->willReturn(new Response());

        $video->getChannelId()
            ->shouldBeCalled()
            ->willReturn('channel_id');

        $this->call->getRow('yt_channel:user:channel_id')
            ->shouldBeCalled()
            ->willReturn([]);

        $this->receiveNewVideo($video);
    }

    public function it_should_not_receive_a_new_video_if_user_is_banned(YTVideo $video, User $user)
    {
        $video->getVideoId()
            ->shouldBeCalled()
            ->willReturn('id');

        $this->repository->getList(['youtube_id' => 'id'])
            ->shouldBeCalled()
            ->willReturn(new Response());

        $video->getChannelId()
            ->shouldBeCalled()
            ->willReturn('channel_id');

        $this->call->getRow('yt_channel:user:channel_id')
            ->shouldBeCalled()
            ->willReturn(['channel_id' => '1']);

        $this->entitiesBuilder->single('1')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isBanned()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->receiveNewVideo($video);
    }

    public function it_should_not_receive_a_new_video_if_user_is_deleted(YTVideo $video, User $user)
    {
        $video->getVideoId()
            ->shouldBeCalled()
            ->willReturn('id');

        $this->repository->getList(['youtube_id' => 'id'])
            ->shouldBeCalled()
            ->willReturn(new Response());

        $video->getChannelId()
            ->shouldBeCalled()
            ->willReturn('channel_id');

        $this->call->getRow('yt_channel:user:channel_id')
            ->shouldBeCalled()
            ->willReturn(['channel_id' => '1']);

        $this->entitiesBuilder->single('1')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isBanned()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getDeleted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->receiveNewVideo($video);
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
}
