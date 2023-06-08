<?php
namespace Minds\Core\Media\Video\Transcoder\TranscodeStorage;

use GuzzleHttp\Client as HttpClient;
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
        
        // AWS client
        $awsConfig = $this->config->get('aws');
        $opts = ['region' => $awsConfig['region'] ?? 'us-east-1'];

        if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
            $opts['credentials'] = [
                'key' => $awsConfig['key'] ?? null,
                'secret' => $awsConfig['secret'] ?? null,
            ];
        }

        // OSS client (S3 compat)
        $ociConfig = $this->config->get('oci')['oss_s3_client'];
        $ociOpts = [
            'region' => $ociConfig['region'] ?? 'us-east-1', // us-east-1 defaults to current OCI region
            'endpoint' => $ociConfig['endpoint'],
            'use_path_style_endpoint' => true, // Required for OSS
            'credentials' => [
                'key' => $ociConfig['key'] ?? null,
                'secret' => $ociConfig['secret'] ?? null,
            ]
        ];

        // API Auth
        $oci_api_config = [
            'tenantId' => $this->config->get('oci')['api_auth']['tenant_id'],
            'userId' => $this->config->get('oci')['api_auth']['user_id'],
            'keyFingerprint' => $this->config->get('oci')['api_auth']['key_fingerprint'],
            'privateKey' => $this->config->get('oci')['api_auth']['private_key'],
            'region' => 'us-ashburn-1',
            'service' => 'objectstorage'
        ];

        // Set primary and secondary clients
        $primaryOpts = $this->config->get('transcoder')['use_oracle_oss'] ? $ociOpts : $opts;
        $secondaryOpts = $this->config->get('transcoder')['use_oracle_oss'] ? $opts : $ociOpts;

        $this->s3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $primaryOpts));
        $this->secondaryS3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $secondaryOpts));
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
     * Create a PresignedUrl for client based uploads
     * @param string $key
     * @return string
     */
    private function getOciPresignedUrl(string $key): string
    {
        $oci_api_config = [
            'tenantId' => $this->config->get('oci')['api_auth']['tenant_id'],
            'userId' => $this->config->get('oci')['api_auth']['user_id'],
            'keyFingerprint' => $this->config->get('oci')['api_auth']['key_fingerprint'],
            'privateKey' => $this->config->get('oci')['api_auth']['private_key'],
            'region' => 'us-ashburn-1',
            'service' => 'objectstorage',
            'bucketName' => 'cinemr',
            'bucketNamespace' => $this->config->get('oci')['api_auth']['bucket_namespace']
        ];

        $privateKey = base64_decode($this->config->get('oci')['api_auth']['private_key'], true);
        if (!$privateKey) {
            throw new \Exception('Unable to load private key');
        }

        // Create Guzzle client
        $client = new HttpClient(['base_uri' => "https://objectstorage.{$oci_api_config['region']}.oraclecloud.com"]);

        // Create pre-authenticated request
        $data = [
            'name' => $key,
            'objectName' => $key,
            'accessType' => 'ObjectWrite',
        ];
        $headers = [
            'Content-Type' => 'application/json',
            'x-content-sha256' => base64_encode(hash('sha256', json_encode($data), true)),
            'date' => gmdate('D, d M Y G:i:s T'),
        ];
        $requestTarget = "(request-target): post /n/{$oci_api_config['bucketNamespace']}/b/{$oci_api_config['bucketName']}/p/";

        // Create the signing string
        $signingString = implode("\n", [
            $requestTarget,
            'date: ' . $headers['date'],
            'content-type: ' . $headers['Content-Type'],
            'x-content-sha256: ' . $headers['x-content-sha256'],
        ]);

        // Create the signature
        openssl_sign($signingString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        // Add Authorization header
        $apiKey = $oci_api_config['tenantId'] . '/' . $oci_api_config['userId'] . '/' . $oci_api_config['keyFingerprint'];
        $headers['Authorization'] = 'Signature version="1",headers="(request-target) date content-type x-content-sha256",keyId="' . $apiKey . '",algorithm="rsa-sha256",signature="' . $signature . '"';

        try {
            $response = $client->request('POST', "/n/{$oci_api_config['bucketNamespace']}/b/{$oci_api_config['bucketName']}/p/", [
                'headers' => $headers,
                'body' => json_encode($data),
            ]);
            $result = json_decode($response->getBody(), true);
            error_log("Pre-authenticated request created with name {$result['name']}\n");
            error_log("Upload URL: {$result['accessUri']}\n");

            return $result['accessUri'];
        } catch (Exception $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * This will return a url that can be used by an HTTP client
     * to upload the source file
     * @param Transcode $transcode
     * @return string
     */
    public function getClientSideUploadUrl(Transcode $transcode): string
    {
        if ($this->config->get('transcoder')['use_oracle_oss']) {
            error_log("Using OCI Presigned URL\n");
            $signedUrl = $this->getOciPresignedUrl("$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}");
        } else {
            error_log("Using AWS Presigned URL\n");
            $cmd = $this->s3->getCommand('PutObject', [
                'Bucket' => 'cinemr',
                'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
            ]);

            $signedUrl = $this->s3->createPresignedRequest($cmd, '+20 minutes')->getUri();
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
            // Attempt to grap from Primary S3
            $this->s3->getObject([
                'Bucket' => 'cinemr',
                'Key' => "$this->dir/{$transcode->getGuid()}/{$transcode->getProfile()->getStorageName()}",
                'SaveAs' => $sourcePath,
            ]);
        } catch (Aws\S3\Exception\S3Exception $e) {
            if ($e->getAwsErrorCode() == 'NoSuchKey') {
                // If does not exist, check Secondary S3
                $this->secondaryS3->getObject([
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
        $awsResult = $this->s3->listObjects([
            'Bucket' => 'cinemr',
            'Prefix' => "{$this->dir}/{$guid}",
        ]);

        $s3Contents = $awsResult['Contents'];
        return array_column($s3Contents, 'Key') ?: [];
    }
}
