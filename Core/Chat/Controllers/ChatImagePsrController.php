<?php
namespace Minds\Core\Chat\Controllers;

use InvalidArgumentException;
use Minds\Core\Chat\Enums\ChatMessageTypeEnum;
use Minds\Core\Chat\Services\ChatImageStorageService;
use Minds\Core\Chat\Services\MessageService;
use Minds\Core\Log\Logger;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Exceptions\NotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\TextResponse;

/**
 * PSR controller for chat images.
 */
class ChatImagePsrController
{
    public function __construct(
        private readonly ChatImageStorageService $imageStorageService,
        private readonly MessageService $messageService,
        private readonly Logger $logger
    ) {
    }

    /**
     * Returns the thumbnail image.
     * @param ServerRequestInterface $request - The request.
     * @return TextResponse - The response.
     */
    public function get(ServerRequestInterface $request): TextResponse
    {
        $roomGuid = $request->getAttribute('parameters')['roomGuid'];
        $messageGuid = $request->getAttribute('parameters')['messageGuid'];
        $user = $request->getAttribute(RequestAttributeEnum::USER);
        $message = null;

        try {
            $message = $this->messageService->getMessage(
                roomGuid: (int) $roomGuid,
                messageGuid: (int) $messageGuid,
                user: $user
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw new NotFoundException('No message found');
        }

        if ($message->messageType !== ChatMessageTypeEnum::IMAGE || !$message->image) {
            throw new NotFoundException('No image found');
        }

        $data = null;
        try {
            $data = $this->imageStorageService->downloadToMemory(
                imageGuid: $message->image->guid,
                ownerGuid: $message->getOwnerGuid()
            );
        } catch (\Exception $e) {
            throw new NotFoundException('No remote asset found');
        }

        return new TextResponse($data, 200, [
            'Content-Type' => 'image/jpeg',
            'Expires' => date('r', strtotime("today+6 months")),
            'Pragma' => 'public',
            'Cache-Control' => 'public',
            'Content-Length' => strlen($data),
            'X-No-Client-Cache' => 0
        ]);
    }

    /**
     * Uploads an image.
     * @param ServerRequestInterface $request - The request.
     * @return JsonResponse - The response.
     */
    public function upload(ServerRequestInterface $request): JsonResponse
    {
        $roomGuid = $request->getAttribute('parameters')['roomGuid'];
        $user = $request->getAttribute(RequestAttributeEnum::USER);

        $uploadedFiles = $request->getUploadedFiles();
        $file = $uploadedFiles['file'] ?? null;
        $imageBlob = $file?->getStream()?->getContents();

        if (!$imageBlob) {
            throw new NotFoundException('No image found');
        }

        // Check mime type is an image
        if (!str_starts_with($file->getClientMediaType(), 'image/')) {
            throw new InvalidArgumentException('Invalid file type. Only images are allowed.');
        }

        $chatMessageEdge = $this->messageService->addMessage(
            roomGuid: (int) $roomGuid,
            user: $user,
            imageBlob: $imageBlob
        );

        return new JsonResponse([
            'status' => 'success',
            'chatMessageEdge' => json_encode($chatMessageEdge)
        ]);
    }
}
