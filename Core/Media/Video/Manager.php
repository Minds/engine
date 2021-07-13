<?php
/**
 * Video Manager
 */
namespace Minds\Core\Media\Video;

use Aws\S3\S3Client;
use Minds\Common;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Entities\Activity;
use Minds\Entities\Entity;
use Minds\Entities\Video;
use Minds\Core\EntitiesBuilder;
use Minds\Common\Repository\Response;
use Minds\Core\Entities\Actions\Save;

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
        $cloudflareStreamsManager = null
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
        $this->s3 = $s3 ?: new S3Client(array_merge(['version' => '2006-03-01'], $opts));
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->transcoderManager = $transcoderManager ?? Di::_()->get('Media\Video\Transcoder\Manager');
        $this->save = $save ?? new Save();
        $this->cloudflareStreamsManager = $cloudflareStreamsManager ?? new CloudflareStreams\Manager();
    }

    /**
     * Return a video
     * @param string $guid
     * @return Video
     */
    public function get($guid): ?Video
    {
        $entity = $this->entitiesBuilder->single($guid);
        if (!$entity instanceof Video) {
            return null;
        }
        return $entity;
    }

    /**
     * Adds a video and creates its transcodes
     */
    public function add(Video $video): bool
    {
        if ($video->getTranscoder() === self::TRANSCODER_CLOUDFLARE) {
            $this->cloudflareStreamsManager->copy($video, $this->getPublicAssetUri($video, 'source'));
        }

        // Save the video
        $success = $this->save->setEntity($video)->save();

        if ($success) {
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
    public function getPublicAssetUri($entity, $size = '360.mp4'): string
    {
        $cmd = null;
        switch (get_class($entity)) {
            case Activity::class:
                // To do
                break;
            case Video::class:
                $cmd = $this->s3->getCommand('GetObject', [
                    'Bucket' => 'cinemr', // TODO: don't hard code
                    'Key' => $this->config->get('transcoder')['dir'] . "/" . $entity->get('cinemr_guid') . "/" . $size,
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
