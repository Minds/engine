<?php
/**
 * Video Manager
 */
namespace Minds\Core\Media\Video;

use Aws\S3\S3Client;
use Exception;
use Minds\Common;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Media\Services\AwsS3Client;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Core\Storage\Quotas\Manager as StorageQuotasManager;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\Video;
use Minds\Exceptions\StopEventException;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;

class Manager
{
    /** @var string */
    const TRANSCODER_MINDS = 'minds';

    /** @var string */
    const TRANSCODER_CLOUDFLARE = 'cloudflare';

    /** @var string[] */
    const VALID_TRANSCODERS = [
        Manager::TRANSCODER_MINDS,
        Manager::TRANSCODER_CLOUDFLARE
    ];

    /** @var Config */
    private $config;

    /** @var S3Client */
    private $s3;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    /** @var Transcoder\Manager */
    private $transcoderManager;

    /** @var Save */
    private $save;

    /** @var CloudflareStreams\Manager */
    private $cloudflareStreamsManager;

    public function __construct(
        $config = null,
        $s3 = null,
        $entitiesBuilder = null,
        $transcoderManager = null,
        $save = null,
        $cloudflareStreamsManager = null,
        protected ?ObjectStorageClient $osClient = null,
        private ?StorageQuotasManager $storageQuotasManager = null
    ) {
        $this->config = $config ?? Di::_()->get('Config');

        // AWS
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

        $this->s3 = $s3 ?? Di::_()->get(AwsS3Client::class);
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->transcoderManager = $transcoderManager ?? Di::_()->get('Media\Video\Transcoder\Manager');
        $this->save = $save ?? new Save();
        $this->cloudflareStreamsManager = $cloudflareStreamsManager ?? new CloudflareStreams\Manager();
        $this->osClient ??= Di::_()->get(ObjectStorageClient::class);
    }

    /**
     * Return a video
     * @param string $guid
     * @return Video
     */
    public function get($guid): ?Video
    {
        $entity = $this->entitiesBuilder->single($guid);

        /**
         * Mobile may send the activity guid instead of the video asset guid,
         * so we will fix that here and return the first video attachment
         */
        if ($entity instanceof Activity && $entity->hasAttachments()) {
            $videoGuid = $entity->attachments[0]['guid'];
            $entity = $this->entitiesBuilder->single($videoGuid);
        }

        if (!$entity instanceof Video) {
            return null;
        }
        return $entity;
    }

    /**
     * Adds a video and creates its transcodes
     * @param Video $video
     * @return bool
     * @throws UnverifiedEmailException
     * @throws StopEventException
     * @throws Exception
     */
    public function add(Video $video): bool
    {
        if ($video->getTranscoder() === self::TRANSCODER_CLOUDFLARE) {
            $this->cloudflareStreamsManager->copy($video, $this->getPublicAssetUri($video, 'source'));
        }

        // Save the video
        $success = $this->save->setEntity($video)->save();

        if ($success) {
            // Update storage quota
            $this->storageQuotasManager->storeAssetQuota($video);

            // Kick off the transcoder
            if ($video->getTranscoder() !== self::TRANSCODER_CLOUDFLARE) {
                $this->transcoderManager->createTranscodes($video);
            }

            return true;
        }

        return false;
    }

    /**
     * Return transcodes
     * @param Video $video
     * @return Source[]
     */
    public function getSources(Video $video): array
    {
        $guid = $video->getGuid();
        if (($legacyGuid = $video->get('cinemr_guid')) && $legacyGuid != $guid) {
            $guid = $legacyGuid;
        }

        switch ($video->getTranscoder()) {
            case self::TRANSCODER_CLOUDFLARE:
                return $this->getCloudflareSources($guid);
            case self::TRANSCODER_MINDS:
            default:
                return $this->getMindsTranscoderSources($guid);
        }
    }

    /**
     * @param string $guid
     * @return Source[]
     */
    private function getMindsTranscoderSources(string $guid): array
    {
        $transcodes = $this->transcoderManager->getList([
            'guid' => $guid,
            'legacyPolyfill' => true,
        ]);
        $sources = [];

        foreach ($transcodes as $transcode) {
            if (str_starts_with($transcode->getProfile()->getStorageName(), '720')) {
                continue;
            }
            if ($transcode->getStatus() != Transcoder\TranscodeStates::COMPLETED) {
                continue;
            }
            if ($transcode->getProfile() instanceof Transcoder\TranscodeProfiles\Thumbnails) {
                continue;
            }
            $source = new Source();
            $source
                ->setGuid($transcode->getGuid())
                ->setType($transcode->getProfile()->getFormat())
                ->setLabel($transcode->getProfile()->getId())
                ->setSize($transcode->getProfile()->getHeight())
                ->setSrc(implode('/', [
                    $this->config->get('transcoder')['cdn_url'] ?? 'https://cdn-cinemr.minds.com',
                    $this->config->get('transcoder')['dir'] ?? '',
                    $transcode->getGuid(),
                    $transcode->getProfile()->getStorageName()
                ]));
            $sources[] = $source;
        }

        // Sort the array so that mp4's are first
        usort($sources, function ($a, $b) {
            if ($a->getType() === 'video/mp4') {
                return -1;
            }
            return 1;
        });

        return $sources;
    }

    /**
     * @param string $guid
     * @return Source[]
     */
    public function getCloudflareSources($guid): array
    {
        $video = $this->get($guid);

        return $this->cloudflareStreamsManager->getSources($video);
    }

    /**
     * Return a public asset uri for entity type
     * @param Entity $entity
     * @param string $size
     * @return string
     */
    public function getPublicAssetUri($entity, $size = '360.mp4'): ?string
    {
        $cmd = null;
        switch (get_class($entity)) {
            case Activity::class:
                // To do
                break;
            case Video::class:
                $key = $this->config->get('transcoder')['dir'] . "/" . $entity->get('cinemr_guid') . "/" . $size;

                // Set primary client
                $useOss = $this->config->get('transcoder')['oci_primary'] ?? false;

                if ($useOss) {
                    $response = $this->osClient->createPreauthenticatedRequest([
                        'namespaceName' => $this->config->get('oci')['api_auth']['bucket_namespace'],
                        'bucketName' => $this->config->get('transcoder')['oci_bucket_name'] ?? 'cinemr',
                        'createPreauthenticatedRequestDetails' => [
                            'name' => $key,
                            'objectName' => $key,
                            'accessType' => 'ObjectRead',
                            'timeExpires' => date('c', strtotime('+20 minutes')),
                        ],
                    ]);
                    
                    return $response->getJson()->fullPath;
                }

                $cmd = $this->s3->getCommand('GetObject', [
                    'Bucket' => 'cinemr', // TODO: don't hard code
                    'Key' => $key,
                ]);
                break;
        }

        if (!$cmd) {
            return null;
        }
        if ($entity->access_id !== Common\Access::PUBLIC) {
            $url = (string)$this->s3->createPresignedRequest($cmd, '+48 hours')->getUri();
        } else {
            $url = $this->config->get('cinemr_url') . $entity->cinemr_guid . '/' . $size;
        }

        return $url;
    }
}
