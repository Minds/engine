<?php
namespace Minds\Core\Media\Video\Transcoder\TranscodeStorage;

use Aws\S3\S3Client;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Media\Video\Transcoder\Transcode;

class S3Storage implements TranscodeStorageInterface
{
    /** @var string */
    private $dir = 'cinemr_data';

    /** @var Config */
    private $config;

    /** @var S3Client */
    private $s3;

    public function __construct($config = null, $s3 = null)
    {
        $this->config = $config ?? Di::_()->get('Config');
        $this->dir = $this->config->get('transcoder')['dir'] ?? '';
        
        $awsConfig = $this->config->get('aws');
        $opts = [
            'region' => $awsConfig['region'] ?? 'us-east-1',
        ];

        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'] ?? null,
                'secret' => $awsConfig['secret'] ?? null,
            ];
        }

        $this->s3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $opts));
    }

    /**
     * Add a transcode to storage
     * @param Transcode $transcode
     * @param string $path
     * @return bool
     */
    public function add(Transcode $transcode, string $path): bool
    {
        return (bool) $this->s3->putObject([
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
        $cmd = $this->s3->getCommand('PutObject', [
            'Bucket' => 'cinemr',
            'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
        ]);

        return (string) $this->s3->createPresignedRequest($cmd, '+20 minutes')->getUri();
    }

    /**
     * @param Transcode $transcode
     * @return string
     */
    public function downloadToTmp(Transcode $transcode): string
    {
        // Create a temporary file where our source file will go
        $sourcePath = tempnam(sys_get_temp_dir(), "{$transcode->getGuid()}-{$transcode->getProfile()->getStorageName()}");

        // Grab from S3
        $this->s3->getObject([
            'Bucket' => 'cinemr',
            'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
            'SaveAs' => $sourcePath,
        ]);

        return $sourcePath;
    }

    /**
     * Return a list of files from storage
     * @param string $guid
     * @return array
     */
    public function ls(string $guid): array
    {
        $awsResult = $this->s3->listObjects([
            'Bucket' => 'cinemr',
            'Prefix' => "{$this->dir}/{$guid}",
        ]);

        $s3Contents = $awsResult['Contents'];
        return array_column($s3Contents, 'Key') ?: [];
    }
}
