<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use DateTime;
use Minds\Core\Chat\Entities\ChatImage;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Media\BlurHash;
use Minds\Entities\User;

/**
 * Service for processing and building chat images.
 */
class ChatImageProcessorService
{
    public function __construct(
        private readonly ChatImageStorageService $imageStorageService,
        private readonly BlurHash $blurHash,
        private readonly Logger $logger
    ) {
    }

    /**
     * Processes a chat image.
     * @param User $user - The user uploading the image.
     * @param string $imageBlob - The image blob.
     * @param int $roomGuid - The room GUID.
     * @param int $messageGuid - The message GUID.
     * @return ChatImage|null - The processed chat image.
     */
    public function process(
        User $user,
        string $imageBlob,
        int $roomGuid,
        int $messageGuid
    ): ?ChatImage {
        $imageGuid = Guid::build();

        try {
            $this->imageStorageService->upload(
                imageGuid: $imageGuid,
                ownerGuid: (string) $user->getGuid(),
                data: $imageBlob
            );

            $imageDimensions = getimagesizefromstring($imageBlob);
            $width = $imageDimensions[0] ?? null;
            $height = $imageDimensions[1] ?? null;
            $blurhash = $this->blurHash->getHash($imageBlob);

            return new ChatImage(
                guid: (int) $imageGuid,
                roomGuid: $roomGuid,
                messageGuid: $messageGuid,
                width: $width,
                height: $height,
                blurhash: $blurhash,
                createdTimestamp: new DateTime(),
                updatedTimestamp: new DateTime()
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }
    }
}
