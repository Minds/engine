<?php
namespace Minds\Core\Media\Audio;

use Aws\S3\S3Client;
use ElggFile;
use Minds\Core\Config\Config;
use Minds\Core\Media\MediaDownloader\MediaDownloaderInterface;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;

class AudioAssetStorageService
{
    public const SOURCE_FILENMAME = 'source';
    public const RESAMPLED_FILENAME = 'resampled.mp3';
    public const THUMBNAIL_FILENAME = 'thumbnail.jpg';

    public function __construct(
        protected readonly Config $config,
        protected readonly S3Client $ociS3,
        protected readonly ObjectStorageClient $osClient,
        protected readonly MediaDownloaderInterface $audioDownloader,
    ) {
    }

    /**
     * Downloads a copy of the audio file to tmp storage
     * @return resource
     */
    public function downloadToTmpfile(AudioEntity $audioEntity, string $filename = self::SOURCE_FILENMAME)
    {
        $tmpfile = tmpfile();
        $tmpfilename = stream_get_meta_data($tmpfile)['uri'];

        if ($audioEntity->remoteFileUrl) {
            $imageResponse = $this->audioDownloader->download($audioEntity->remoteFileUrl);
            $imageStream = $imageResponse->getBody();
            while ($chunk = $imageStream->read(1024)) {
                fwrite($tmpfile, $chunk);
            }

        } else {
            $this->ociS3->getObject([
                'Bucket' => $this->getBucketName(),
                'Key' => $this->getFilepath($audioEntity) . '/' . $filename,
                'SaveAs' => $tmpfilename,
            ]);
        }

        return $tmpfile;
    }

    /**
     * Downloads a copy of the audio file to tmp storage
     */
    public function downloadToMemory(AudioEntity $audioEntity, string $filename = self::SOURCE_FILENMAME): string
    {
        $result = $this->ociS3->getObject([
            'Bucket' => $this->getBucketName(),
            'Key' => $this->getFilepath($audioEntity) . '/' . $filename,
        ]);

        return $result['Body'];
    }

    /**
     * Returns a url to download the asset directly from the S3 bucket
     */
    public function getDownloadUrl(AudioEntity $audioEntity, string $filename = self::SOURCE_FILENMAME): string
    {
        $key = $this->getFilepath($audioEntity) . '/' . $filename;
        $response = $this->osClient->createPreauthenticatedRequest([
            'namespaceName' => $this->config->get('oci')['api_auth']['bucket_namespace'],
            'bucketName' => $this->getBucketName(),
            'createPreauthenticatedRequestDetails' => [
                'name' => $key,
                'objectName' => $key,
                'accessType' => 'ObjectRead',
                'timeExpires' => date('c', strtotime('+1 day')),
            ],
        ]);

        return $response->getJson()->fullPath;
    }

    /**`
     * Uploads an asset to the filestore
     */
    public function upload(
        AudioEntity $audioEntity,
        string $source = null,
        string $data = null,
        string $filename = self::RESAMPLED_FILENAME
    ): bool {

        $this->ociS3->putObject([
            'Bucket' => $this->getBucketName(),
            'Key' => $this->getFilepath($audioEntity) . '/' . $filename,
            'Body' => $source ? fopen($source, 'r') : $data,
        ]);

        return true;
    }

    /**
     * This will return a url that can be used by an HTTP client
     * to upload the source file
     */
    public function getClientSideUploadUrl(AudioEntity $audio): string
    {
        $signedUrl = $this->getOciPresignedUrl($this->getFilepath($audio) . '/' . self::SOURCE_FILENMAME);

        return (string) $signedUrl;
    }

    /**
     * Returns the full filepath, of where the audio asset is stored
     */
    private function getFilepath(AudioEntity $audio): string
    {
        $fakeFile = new ElggFile();
        $fakeFile->setFilename('audio/' . $audio->guid);
        $fakeFile->owner_guid = $audio->ownerGuid;

        return $fakeFile->getFilenameOnFilestore();
    }
    
    /**
     * Create a PresignedUrl for client based uploads
     */
    private function getOciPresignedUrl(string $key): string
    {
        $response = $this->osClient->createPreauthenticatedRequest([
            'namespaceName' => $this->config->get('oci')['api_auth']['bucket_namespace'],
            'bucketName' => $this->getBucketName(),
            'createPreauthenticatedRequestDetails' => [
                'name' => $key,
                'objectName' => $key,
                'accessType' => 'ObjectWrite',
                'timeExpires' => date('c', strtotime('+20 minutes')),
            ],
        ]);

        return $response->getJson()->fullPath;
    }

    /**
     * The bucket name of where the assets are stored
     */
    private function getBucketName(): string
    {
        return $this->config->get('storage')['oci_bucket_name'] ?? 'mindsfs';
    }
}
