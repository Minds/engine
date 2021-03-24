<?php

namespace Spec\Minds\Core\Media\ClientUpload;

use Minds\Core\Media\ClientUpload\Manager;
use Minds\Core\Media\ClientUpload\ClientUploadLease;
use Minds\Core\Media\Video\Transcoder;
use Minds\Core\GuidBuilder;
use Minds\Core\Media\Video\Manager as VideoManager;
use Minds\Entities\User;
use Minds\Entities\Video;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Transcoder\Manager */
    private $transcoderManager;
 
    /** @var VideoManager */
    private $videoManager;

    /** @var GuidBuilder */
    private $guid;

    public function let(Transcoder\Manager $transcoderManager, VideoManager $videoManager, GuidBuilder $guid)
    {
        $this->beConstructedWith($transcoderManager, $videoManager, $guid);
        $this->transcoderManager = $transcoderManager;
        $this->videoManager = $videoManager;
        $this->guid = $guid;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_an_upload_lease()
    {
        $this->guid->build()
            ->willReturn(123);

        $this->transcoderManager->getClientSideUploadUrl(Argument::type(Video::class))
            ->shouldBeCalled()
            ->willReturn('s3-url-here');

        $lease = $this->prepare('video');

        $lease->getMediaType()
            ->shouldBe('video');
        $lease->getGuid()
            ->shouldBe(123);
        $lease->getPresignedUrl()
            ->shouldBe('s3-url-here');
    }

    public function it_should_complete_an_upload(ClientUploadLease $lease, User $user)
    {
        $lease->getMediaType()
            ->willReturn('video');

        $lease->getGuid()
            ->willReturn(456);

        $lease->getUser()
            ->willReturn($user);

        $user->isPro()
            ->willReturn(true);

        $this->videoManager->add(Argument::that(function ($video) {
            return $video->guid == 456
                && $video->access_id == 0;
        }))
            ->shouldBeCalled();

        $this->complete($lease)
            ->shouldReturn(true);
    }
}
