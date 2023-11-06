<?php
/**
 * Minds Media Provider.
 */

namespace Minds\Core\Media;

use Aws\S3\S3Client;
use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider;
use Minds\Core\Entities\Actions\Save;
use Oracle\Oci\Common\Auth\UserAuthProvider;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;

class MediaProvider extends Provider
{
    public function register()
    {
        $this->di->bind('Media\Image\Manager', function ($di) {
            return new Image\Manager();
        }, ['useFactory' => true]);
        $this->di->bind('Media\Video\Manager', function ($di) {
            return new Video\Manager();
        }, ['useFactory' => true]);
        $this->di->bind('Media\Albums', function ($di) {
            return new Albums(new Core\Data\Call('entities_by_time'));
        }, ['useFactory' => true]);

        $this->di->bind('Media\Feeds', function ($di) {
            return new Feeds();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Repository', function ($di) {
            return new Repository();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Thumbnails', function ($di) {
            return new Thumbnails($di->get('Config'));
        }, ['useFactory' => true]);

        $this->di->bind('Media\Recommended', function ($di) {
            return new Recommended();
        }, ['useFactory' => true]);

        // Proxy

        $this->di->bind('Media\Proxy\Download', function ($di) {
            return new Proxy\Download();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Proxy\Resize', function ($di) {
            return new Proxy\Resize();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Proxy\MagicResize', function ($di) {
            return new Proxy\MagicResize();
        }, ['useFactory' => true]);

        // Imagick

        $this->di->bind('Media\Imagick\Autorotate', function ($di) {
            return new Imagick\Autorotate();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Imagick\Resize', function ($di) {
            return new Imagick\Resize();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Imagick\Annotate', function ($di) {
            return new Imagick\Annotate();
        }, ['useFactory' => true]);

        $this->di->bind('Media\Imagick\Manager', function ($di) {
            return new Imagick\Manager();
        }, ['useFactory' => false]);

        // Blurhash

        $this->di->bind('Media\BlurHash', function ($di) {
            return new BlurHash();
        }, ['useFactory' => true]);

        // ClientUpload

        $this->di->bind('Media\ClientUpload\Manager', function ($di) {
            return new ClientUpload\Manager();
        }, ['useFactory' => true]);


        // Transcoder

        $this->di->bind('Media\Video\Transcoder\Manager', function ($di) {
            return new Video\Transcoder\Manager();
        }, ['useFactory' => false]);

        $this->di->bind('Media\Video\Transcoder\TranscodeStates', function ($di) {
            return new Video\Transcoder\TranscodeStates();
        }, ['useFactory' => false]);

        $this->di->bind('Media\Video\Transcode\TranscodeStorage', function ($di) {
            return new Video\Transcoder\TranscodeStorage\S3Storage();
        }, ['useFactory' => false]);

        // OCI

        $this->di->bind(UserAuthProvider::class, function ($di) {
            $config = $di->get('Config');
            return new UserAuthProvider(
                tenancy_id: $config->get('oci')['api_auth']['tenant_id'],
                user_id: $config->get('oci')['api_auth']['user_id'],
                fingerprint: $config->get('oci')['api_auth']['key_fingerprint'],
                private_key: base64_decode($config->get('oci')['api_auth']['private_key'], true),
            );
        }, ['useFactory' => true]);

        $this->di->bind(ObjectStorageClient::class, function ($di) {
            $authProvider = $di->get(UserAuthProvider::class);
            return new ObjectStorageClient($authProvider, 'us-ashburn-1');
        }, ['useFactory' => true]);

        // S3 Clients

        $this->di->bind(Services\OciS3Client::class, function ($di) {
            $config = $di->get('Config');
            $ociConfig = $config->get('oci')['oss_s3_client'];
            $opts = [
                'region' => $ociConfig['region'] ?? 'us-east-1', // us-east-1 defaults to current OCI region
                'endpoint' => $ociConfig['endpoint'],
                'use_path_style_endpoint' => true, // Required for OSS
                'credentials' => [
                    'key' => $ociConfig['key'] ?? null,
                    'secret' => $ociConfig['secret'] ?? null,
                ]
            ];

            return new S3Client(array_merge(['version' => '2006-03-01'], $opts));
        }, ['useFactory' => true]);

        $this->di->bind(Services\AwsS3Client::class, function ($di) {
            $config = $di->get('Config');
            $awsConfig = $config->get('aws');
            $opts = [
                'region' => $awsConfig['region'] ?? 'us-east-1',
                'http' => [
                    'connect_timeout' => 1, //if we don't connect in 1 second
                    'timeout' => 120 //if the request takes longer than 2 minutes (120 seconds)
                ],
                'use_accelerate_endpoint' => true,
            ];

            if (!isset($awsConfig['useRoles']) || !$awsConfig['useRoles']) {
                $opts['credentials'] = [
                    'key' => $awsConfig['key'] ?? null,
                    'secret' => $awsConfig['secret'] ?? null,
                ];
            }

            return new S3Client(array_merge(['version' => '2006-03-01'], $opts));
        }, ['useFactory' => true]);

        $this->di->bind(Image\ProcessExternalImageService::class, function (Di $di): Image\ProcessExternalImageService {
            return new Image\ProcessExternalImageService(
                $di->get(\GuzzleHttp\Client::class),
                new Assets\Image(),
                new Save(),
            );
        });
    }
}
