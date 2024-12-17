<?php
declare(strict_types=1);

namespace Minds\Core\Chat\Services;

use Aws\S3\S3Client;
use ElggFile;
use Minds\Core\Config\Config;

/**
 * Service to handle the storage and retrieval of chat images.
 */
class ChatImageStorageService
{
    public function __construct(
        protected readonly Config $config,
        protected readonly S3Client $ociS3
    ) {
    }

    /**
     * Downloads a copy of the image file to memory.
     * @param string $imageGuid - The image guid.
     * @param string $ownerGuid - The owner guid.
     * @return string - The image data.
     */
    public function downloadToMemory(string $imageGuid, string $ownerGuid): string
    {
        $result = $this->ociS3->getObject([
            'Bucket' => $this->getBucketName(),
            'Key' => $this->getFilepath($imageGuid, $ownerGuid),
        ]);
        return $result['Body']->getContents();
    }

    /**
     * Uploads an asset to the filestore.
     * @param string $imageGuid - The image guid.
     * @param string $ownerGuid - The owner guid.
     * @param string|null $data - The image data.
     * @return bool - If the upload was successful.
     */
    public function upload(
        string $imageGuid,
        string $ownerGuid,
        string $data = null,
    ): bool {
        $this->ociS3->putObject([
            'Bucket' => $this->getBucketName(),
            'Key' =>  $this->getFilepath($imageGuid, $ownerGuid),
            'Body' => $data,
        ]);
        return true;
    }

    /**
     * Deletes an image from the filestore.
     * @param string $imageGuid - The image guid.
     * @param string $ownerGuid - The owner guid.
     * @return bool - If the deletion was successful.
     */
    public function delete(
        string $imageGuid,
        string $ownerGuid,
    ): bool {
        $this->ociS3->deleteObject([
            'Bucket' => $this->getBucketName(),
            'Key' => $this->getFilepath($imageGuid, $ownerGuid),
        ]);
        return true;
    }

    /**
     * Returns the full filepath, of where the audio asset is stored.
     * @param string $imageGuid - The image guid.
     * @param string $ownerGuid - The owner guid.
     * @return string - The filepath.
     */
    private function getFilepath(string $imageGuid, string $ownerGuid): string
    {
        $fakeFile = new ElggFile();
        $fakeFile->setFilename('chat/images/' . $imageGuid);
        $fakeFile->owner_guid = $ownerGuid;
        return $fakeFile->getFilenameOnFilestore();
    }

    /**
     * Gets the bucket name from config.
     * @return string - The bucket name.
     */
    private function getBucketName(): string
    {
        return $this->config->get('storage')['oci_bucket_name'] ?? 'mindsfs';
    }
}
