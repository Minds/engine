<?php

namespace Spec\Minds\Core\Media\YouTubeImporter;

use Minds\Core\Media\YouTubeImporter\YTSubscription;
use Minds\Core\Media\YouTubeImporter\YTClient;
use Minds\Core\Media\YouTubeImporter\YTVideo;
use Minds\Core\Media\YouTubeImporter\Manager;
use Minds\Core\Media\YouTubeImporter\Repository;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Config;
use Minds\Core\Data\Call;
use Minds\Core\Entities\Actions\Save;
use Minds\Common\Repository\Response;
use Minds\Entities\User;
use Minds\Entities\Video;
use Pubsubhubbub\Subscriber\Subscriber;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class YTSubscriptionSpec extends ObjectBehavior
{
    /** @var YTClient */
    protected $ytClient;

    /** @var Manager */
    protected $manager;

    /** @var Repository */
    protected $repository;

    /** @var Subscriber */
    protected $subscriber;

    /** @var Config */
    protected $config;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    /** @var Save */
    protected $save;

    /** @var Call */
    protected $db;

    public function let(
        YTClient $ytClient,
        Manager $manager,
        Repository $repository,
        Subscriber $subscriber,
        Config $config,
        EntitiesBuilder $entitiesBuilder,
        Save $save,
        Call $db
    ) {
        $this->ytClient = $ytClient;
        $this->manager = $manager;
        $this->repository = $repository;
        $this->subscriber = $subscriber;
        $this->config = $config;
        $this->entitiesBuilder = $entitiesBuilder;
        $this->save = $save;
        $this->db = $db;
        $this->beConstructedWith(
            $ytClient,
            $manager,
            $repository,
            $subscriber,
            $config,
            $entitiesBuilder,
            $save,
            $db
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(YTSubscription::class);
    }

    public function it_should_not_receive_a_new_video_if_it_already_exists(YTVideo $video, Video $video2, User $user)
    {
        $video->getVideoId()
            ->shouldBeCalled()
            ->willReturn('id');

        $this->repository->getList(['youtube_id' => 'id'])
            ->shouldBeCalled()
            ->willReturn(new Response([$video2]));

        $this->onNewVideo($video);
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

        $this->db->getRow('yt_channel:user:channel_id')
            ->shouldBeCalled()
            ->willReturn([]);

        $this->onNewVideo($video);
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

        $this->db->getRow('yt_channel:user:channel_id')
            ->shouldBeCalled()
            ->willReturn([0 => '1']);

        $this->entitiesBuilder->single('1')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isBanned()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onNewVideo($video);
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

        $this->db->getRow('yt_channel:user:channel_id')
            ->shouldBeCalled()
            ->willReturn([0 => '1']);

        $this->entitiesBuilder->single('1')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->isBanned()
            ->shouldBeCalled()
            ->willReturn(false);

        $user->getDeleted()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onNewVideo($video);
    }
}
