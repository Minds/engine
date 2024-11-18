<?php

namespace Spec\Minds\Core\Media\ClientUpload;

use Minds\Core\Media\ClientUpload\Manager;
use Minds\Core\Media\ClientUpload\ClientUploadLease;
use Minds\Core\Media\Video\Transcoder;
use Minds\Core\GuidBuilder;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Media\ClientUpload\MediaTypeEnum;
use Minds\Core\Media\Video\Manager as VideoManager;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Entities\User;
use Minds\Entities\Video;

use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    /** @var Transcoder\Manager */
    private $transcoderManager;
 
    /** @var VideoManager */
    private $videoManager;

    /** @var GuidBuilder */
    private $guid;

    private Collaborator $rbacGatekeeperServiceMock;

    public function let(
        Transcoder\Manager $transcoderManager,
        VideoManager $videoManager,
        GuidBuilder $guid,
        RbacGatekeeperService $rbacGatekeeperServiceMock,
        AudioService $audioServiceMock,
    ) {
        $this->beConstructedWith($transcoderManager, $videoManager, $guid, $rbacGatekeeperServiceMock, $audioServiceMock);
        $this->transcoderManager = $transcoderManager;
        $this->videoManager = $videoManager;
        $this->guid = $guid;
        $this->rbacGatekeeperServiceMock = $rbacGatekeeperServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_return_an_upload_lease()
    {
        $this->rbacGatekeeperServiceMock->isAllowed(PermissionsEnum::CAN_UPLOAD_VIDEO)->willReturn(true);

        $this->guid->build()
            ->willReturn(123);

        $this->transcoderManager->getClientSideUploadUrl(Argument::type(Video::class))
            ->shouldBeCalled()
            ->willReturn('s3-url-here');

        $lease = $this->prepare(MediaTypeEnum::VIDEO, new User());

        $lease->mediaType
            ->shouldBe(MediaTypeEnum::VIDEO);
        $lease->guid
            ->shouldBe(123);
        $lease->presignedUrl
            ->shouldBe('s3-url-here');
    }

    public function it_should_complete_an_upload(User $user)
    {
        $lease = new ClientUploadLease(
            guid: 456,
            mediaType: MediaTypeEnum::VIDEO
        );

        $user->isPro()
            ->willReturn(true);

        $user->getGuid()
            ->willReturn('123');

        $this->videoManager->add(Argument::that(function ($video) {
            return $video->guid == 456
                && $video->access_id == 0;
        }))
            ->shouldBeCalled();

        $this->complete($lease, $user)
            ->shouldReturn(true);
    }
}
