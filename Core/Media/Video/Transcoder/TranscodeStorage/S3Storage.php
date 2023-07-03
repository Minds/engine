<?php
namespace Minds\Core\Media\Video\Transcoder\TranscodeStorage;

use Aws\S3\Exception\S3Exception;
use GuzzleHttp\Client as HttpClient;
use Aws\S3\S3Client;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Media\Services\AwsS3Client;
use Minds\Core\Media\Services\OciS3Client;
use Minds\Core\Media\Video\Transcoder\Transcode;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use Oracle\Oci\Common\Auth\UserAuthProvider;

class S3Storage implements TranscodeStorageInterface
{
    /** @var string */
    private $dir = 'cinemr_data';

    public function __construct(
        protected ?Config $config = null,
        protected ?S3Client $awsS3 = null,
        protected ?S3Client $ociS3 = null,
        protected ?ObjectStorageClient $osClient = null,
    ) {
        $this->config ??= Di::_()->get('Config');
        $this->dir = $this->config->get('transcoder')['dir'] ?? '';
        
        $this->awsS3 ??= Di::_()->get(AwsS3Client::class);
        $this->ociS3 ??= Di::_()->get(OciS3Client::class);

        $this->osClient ??= Di::_()->get(ObjectStorageClient::class);
    }

    /**
     * @deprecated No longer used since Cloudflare Streams
     * Add a transcode to storage
     * @param Transcode $transcode
     * @param string $path
     * @return bool
     */
    public function add(Transcode $transcode, string $path): bool
    {
        return (bool) $this->awsS3->putObject([
            'ACL' => 'public-read',
            'Bucket' => 'cinemr',
            'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
            'Body' => fopen($path, 'r'),
        ]);
    }

    /**
     * This will return a url that can be used by an HTTP client
     * to upload the source file
     * @param Transcode $transcode
     * @return string
     */
    public function getClientSideUploadUrl(Transcode $transcode): string
    {
        if ($this->config->get('transcoder')['oci_primary']) {
            $signedUrl = $this->getOciPresignedUrl("$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}");
        } else {
            $cmd = $this->awsS3->getCommand('PutObject', [
                'Bucket' => 'cinemr',
                'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
            ]);

            $signedUrl = $this->awsS3->createPresignedRequest($cmd, '+20 minutes')->getUri();
        }

        return (string) $signedUrl;
    }

    /**
     * @param Transcode $transcode
     * @return string
     */
    public function downloadToTmp(Transcode $transcode): string
    {
        // Create a temporary file where our source file will go
        $sourcePath = tempnam(sys_get_temp_dir(), "{$transcode->getGuid()}-{$transcode->getProfile()->getStorageName()}");

        try {
            // Attempt to grab from OCI
            $this->ociS3->getObject([
                'Bucket' => $this->config->get('transcoder')['oci_bucket_name'] ?? 'cinemr',
                'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
                'SaveAs' => $sourcePath,
            ]);
        } catch (S3Exception $e) {
            if ($e->getAwsErrorCode() == 'NoSuchKey') {
                // If does not exist, check Secondary S3
                $this->awsS3->getObject([
                    'Bucket' => 'cinemr',
                    'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
                    'SaveAs' => $sourcePath,
                ]);
            } else {
                throw $e;
            }
        }

        return $sourcePath;
    }

    /**
     * Return a list of files from storage
     * @param string $guid
     * @return array
     */
    public function ls(string $guid): array
    {
        $awsResult = $this->awsS3->listObjects([
            'Bucket' => 'cinemr',
            'Prefix' => "{$this->dir}/{$guid}",
        ]);

        $s3Contents = $awsResult['Contents'];
        return array_column($s3Contents, 'Key') ?: [];
    }

    /**
     * Create a PresignedUrl for client based uploads
     * @param string $key
     * @return string
     */
    private function getOciPresignedUrl(string $key): string
    {
        $response = $this->osClient->createPreauthenticatedRequest([
            'namespaceName' => $this->config->get('oci')['api_auth']['bucket_namespace'],
            'bucketName' => $this->config->get('transcoder')['oci_bucket_name'] ?? 'cinemr',
            'createPreauthenticatedRequestDetails' => [
                'name' => $key,
                'objectName' => $key,
                'accessType' => 'ObjectWrite',
                'timeExpires' => date('c', strtotime('+20 minutes')),
            ],
        ]);
        
        return $response->getJson()->fullPath;
    }
}
