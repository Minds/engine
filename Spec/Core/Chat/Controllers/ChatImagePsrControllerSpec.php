<?php

namespace Spec\Minds\Core\Chat\Controllers;

use DateTime;
use Minds\Core\Chat\Controllers\ChatImagePsrController;
use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Chat\Entities\ChatMessage;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Chat\Types\ChatMessageEdge;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;

class ChatImagePsrControllerSpec extends ObjectBehavior
{
    private $imageStorageServiceMock;
    private $messageServiceMock;
    private $loggerMock;

    public function let(
        ChatImageStorageService $imageStorageServiceMock,
        MessageService $messageServiceMock,
        ACL $aclMock,
        Logger $loggerMock
    ) {
        $this->imageStorageServiceMock = $imageStorageServiceMock;
        $this->messageServiceMock = $messageServiceMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith($imageStorageServiceMock, $messageServiceMock, $loggerMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ChatImagePsrController::class);
    }

    // get.

    public function it_should_get_an_image(
        ServerRequestInterface $request,
        User $user,
    ) {
        $roomGuid = 1;
        $messageGuid = 3;
        $imageGuid = 4;
        $senderGuid = 5;

        $chatImage = new ChatImage(
            guid: $imageGuid,
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            width: 100,
            height: 100,
            blurhash: 'blurhash',
            createdTimestamp: new DateTime('2024-01-01'),
            updatedTimestamp: new DateTime('2024-02-01')
        );
        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: 'plainText',
            messageType: ChatMessageTypeEnum::IMAGE,
            image: $chatImage,
            richEmbed: null,
            createdAt: new DateTime('2024-01-01')
        );

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => $roomGuid, 'messageGuid' => $messageGuid]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->messageServiceMock->getMessage(
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            user: $user
        )
            ->shouldBeCalled()
            ->willReturn($chatMessage);

        $this->imageStorageServiceMock->downloadToMemory(
            imageGuid: $imageGuid,
            ownerGuid: $senderGuid
        )
            ->shouldBeCalled()
            ->willReturn('imageBlob');

        $this->get($request)->shouldBeAnInstanceOf(TextResponse::class);
    }

    public function it_should_throw_if_no_message_found(
        ServerRequestInterface $request,
        User $user,
    ) {
        $roomGuid = 1;
        $messageGuid = 3;

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => $roomGuid, 'messageGuid' => $messageGuid]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->messageServiceMock->getMessage(
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            user: $user
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('No message found'));

        $this->shouldThrow(NotFoundException::class)->duringGet($request);
    }

    public function it_should_throw_if_is_not_image_type(
        ServerRequestInterface $request,
        User $user,
    ) {
        $roomGuid = 1;
        $messageGuid = 3;
        $imageGuid = 4;
        $senderGuid = 5;

        $chatImage = new ChatImage(
            guid: $imageGuid,
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            width: 100,
            height: 100,
            blurhash: 'blurhash',
            createdTimestamp: new DateTime('2024-01-01'),
            updatedTimestamp: new DateTime('2024-02-01')
        );
        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: 'plainText',
            messageType: ChatMessageTypeEnum::TEXT,
            image: $chatImage,
            richEmbed: null,
            createdAt: new DateTime('2024-01-01')
        );

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => $roomGuid, 'messageGuid' => $messageGuid]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->messageServiceMock->getMessage(
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            user: $user
        )
            ->shouldBeCalled()
            ->willReturn($chatMessage);

        $this->shouldThrow(NotFoundException::class)->duringGet($request);
    }

    public function it_should_throw_if_no_image_found(
        ServerRequestInterface $request,
        User $user,
    ) {
        $roomGuid = 1;
        $messageGuid = 3;
        $senderGuid = 5;

        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: 'plainText',
            messageType: ChatMessageTypeEnum::IMAGE,
            image: null,
            richEmbed: null,
            createdAt: new DateTime('2024-01-01')
        );

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => $roomGuid, 'messageGuid' => $messageGuid]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->messageServiceMock->getMessage(
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            user: $user
        )
            ->shouldBeCalled()
            ->willReturn($chatMessage);

        $this->shouldThrow(NotFoundException::class)->duringGet($request);
    }

    public function it_should_throw_if_no_remote_asset_found(
        ServerRequestInterface $request,
        User $user,
    ) {
        $roomGuid = 1;
        $messageGuid = 3;
        $imageGuid = 4;
        $senderGuid = 5;

        $chatImage = new ChatImage(
            guid: $imageGuid,
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            width: 100,
            height: 100,
            blurhash: 'blurhash',
            createdTimestamp: new DateTime('2024-01-01'),
            updatedTimestamp: new DateTime('2024-02-01')
        );
        $chatMessage = new ChatMessage(
            roomGuid: $roomGuid,
            guid: $messageGuid,
            senderGuid: $senderGuid,
            plainText: 'plainText',
            messageType: ChatMessageTypeEnum::IMAGE,
            image: $chatImage,
            richEmbed: null,
            createdAt: new DateTime('2024-01-01')
        );

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => $roomGuid, 'messageGuid' => $messageGuid]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $this->messageServiceMock->getMessage(
            roomGuid: $roomGuid,
            messageGuid: $messageGuid,
            user: $user
        )
            ->shouldBeCalled()
            ->willReturn($chatMessage);

        $this->imageStorageServiceMock->downloadToMemory(
            imageGuid: $imageGuid,
            ownerGuid: $senderGuid
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('No remote asset found'));

        $this->shouldThrow(NotFoundException::class)->duringGet($request);
    }

    // upload.

    public function it_should_upload_an_image(
        ServerRequestInterface $request,
        UploadedFileInterface $file,
        StreamInterface $stream,
        User $user,
        ChatMessageEdge $chatMessageEdge,
    ) {
        $roomGuid = 1;
        $imageBlob = 'imageBlob';

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => 1]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getUploadedFiles()
            ->shouldBeCalled()
            ->willReturn([
                'file' => $file
            ]);

        $file->getStream()
            ->shouldBeCalled()
            ->willReturn($stream);

        $stream->getContents()
            ->shouldBeCalled()
            ->willReturn($imageBlob);

        $file->getClientMediaType()
            ->shouldBeCalled()
            ->willReturn('image/png');

        $this->messageServiceMock->addMessage(
            roomGuid: $roomGuid,
            user: $user,
            messge: null,
            imageBlob: $imageBlob
        )
            ->shouldBeCalled()
            ->willReturn($chatMessageEdge);

        $response = $this->upload($request);
        $response->shouldBeAnInstanceOf(JsonResponse::class);
        $response->getStatusCode()->shouldBe(200);
    }

    public function it_should_throw_if_no_file_uploaded(
        ServerRequestInterface $request,
        User $user,
    ) {
        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => 1]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getUploadedFiles()
            ->willReturn([]);

        $this->shouldThrow(\Exception::class)->duringUpload($request);
    }

    public function it_should_throw_if_invalid_mime_type(
        ServerRequestInterface $request,
        UploadedFileInterface $file,
        User $user,
        StreamInterface $stream,
    ) {
        $imageBlob = 'imageBlob';

        $request->getAttribute('parameters')
            ->shouldBeCalled()
            ->willReturn(['roomGuid' => 1]);

        $request->getAttribute(RequestAttributeEnum::USER)
            ->shouldBeCalled()
            ->willReturn($user);

        $request->getUploadedFiles()
            ->shouldBeCalled()
            ->willReturn([
                'file' => $file
            ]);

        $file->getStream()
            ->shouldBeCalled()
            ->willReturn($stream);

        $stream->getContents()
            ->shouldBeCalled()
            ->willReturn($imageBlob);

        $file->getClientMediaType()
            ->willReturn('application/pdf');

        $this->shouldThrow(\InvalidArgumentException::class)->duringUpload($request);
    }
}
