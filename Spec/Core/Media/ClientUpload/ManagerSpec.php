<?php

namespace Spec\Minds\Core\Media\ClientUpload;

use Minds\Core\Media\ClientUpload\Manager;
use Minds\Core\Media\ClientUpload\ClientUploadLease;
use Minds\Core\Media\Video\Transcoder;
use Minds\Core\GuidBuilder;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;
use Minds\Entities\Video;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private $transcoderManager;
    private $guid;
    private $save;

    public function let(Transcoder\Manager $transcoderManager, GuidBuilder $guid, Save $save)
    {
        $this->beConstructedWith($transcoderManager, $guid, $save);
        $this->transcoderManager = $transcoderManager;
        $this->guid = $guid;
        $this->save = $save;
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

        $this->save->setEntity(Argument::that(function ($video) {
            return $video->guid == 456
                && $video->access_id == 0;
        }))
            ->shouldBeCalled()
            ->willReturn($this->save);

        $this->save->save()
            ->shouldBeCalled();

        $this->transcoderManager->createTranscodes(Argument::type(Video::class))
            ->shouldBeCalled();

        $this->complete($lease)
            ->shouldReturn(true);
    }
}
