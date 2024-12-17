<?php

namespace Spec\Minds\Core\Chat\Services;

use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Chat\Services\ChatImageProcessorService;
use PhpSpec\ObjectBehavior;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Log\Logger;
use Minds\Core\Media\BlurHash;
use Minds\Entities\User;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ChatImageProcessorServiceSpec extends ObjectBehavior
{
    protected Collaborator $imageStorageServiceMock;
    protected Collaborator $blurHashMock;
    protected Collaborator $loggerMock;

    public function let(
        ChatImageStorageService $imageStorageServiceMock,
        BlurHash $blurHashMock,
        Logger $loggerMock
    ) {
        $this->imageStorageServiceMock = $imageStorageServiceMock;
        $this->blurHashMock = $blurHashMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($imageStorageServiceMock, $blurHashMock, $loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatImageProcessorService::class);
    }

    public function it_should_process_image(
        User $user,
    ) {
        $imageBlob = 1;
        $roomGuid = 2;
        $messageGuid = 3;
        $userGuid = 4;

        $user->getGuid()
            ->willReturn($userGuid);

        $this->imageStorageServiceMock->upload(
            imageGuid: Argument::any(),
            ownerGuid: $userGuid,
            data: $imageBlob
        )
            ->shouldBeCalled();

        $this->imageStorageServiceMock->upload(
            imageGuid: Argument::any(),
            ownerGuid: $userGuid,
            data: $imageBlob
        )
            ->shouldBeCalled();

        $this->blurHashMock->getHash(Argument::any())
            ->willReturn('test-blurhash');

        $this->process($user, $imageBlob, $roomGuid, $messageGuid)
            ->shouldReturnAnInstanceOf(ChatImage::class);
    }
}
