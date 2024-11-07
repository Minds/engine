<?php
namespace Minds\Core\Media\Audio;

use Minds\Core\Media\Imagick\Manager as ImagickManager;
use Minds\Exceptions\UserErrorException;

class AudioThumbnailService
{
    public function __construct(
        private readonly AudioAssetStorageService $audioAssetStorageService,
        private readonly ImagickManager $imagickManager,
    ) {
        
    }

    /**
     * Processes and uploads the thumbnail for an audio entity
     */
    public function process(AudioEntity $audioEntity, string $blob)
    {
        $blobParts = explode(',', $blob);

        if (!isset($blobParts[1])) {
            throw new UserErrorException("Invalid image type");
        }

        $blob = $blobParts[1];

        $blob = base64_decode($blob, true);

        $imageData = $this->imagickManager
            ->setImageFromBlob($blob)
            ->getJpeg();

        $this->audioAssetStorageService->upload(
            audioEntity: $audioEntity,
            data: $imageData,
            filename: AudioAssetStorageService::THUMBNAIL_FILENAME
        );

        return true;
    }

    /**
     * Returns the data of the thumbnail
     */
    public function get(AudioEntity $audioEntity): string
    {
        try {
            $data = $this->audioAssetStorageService->downloadToMemory(
                audioEntity: $audioEntity,
                filename: AudioAssetStorageService::THUMBNAIL_FILENAME
            );

            if ($data) {
                return $data;
            }
        } catch (\Exception) {
        }

        return file_get_contents(__MINDS_ROOT__ . '/Assets/photos/default-audio.jpg');
    }
}
