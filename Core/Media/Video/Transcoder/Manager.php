<?php
/**
 * Transcoder manager
 */
namespace Minds\Core\Media\Video\Transcoder;

use Minds\Core\Media\Video\Transcoder\Delegates\QueueDelegate;
use Minds\Core\Media\Video\Transcoder\Delegates\NotificationDelegate;
use Minds\Core\Media\Video\Transcoder\Delegates\MetadataDelegate;
use Minds\Entities\Video;
use Minds\Traits\MagicAttributes;
use Minds\Common\Repository\Response;
use Minds\Core\Media\Video\Source;
use Minds\Exceptions\DeprecatedException;

class Manager
{
    /** @var TranscodeProfileInterface[] */
    const TRANSCODE_PROFILES = [
        TranscodeProfiles\Thumbnails::class,
        TranscodeProfiles\X264_360p::class,
        TranscodeProfiles\X264_720p::class,
        TranscodeProfiles\X264_1080p::class,
        TranscodeProfiles\Webm_360p::class,
        TranscodeProfiles\Webm_720p::class,
        TranscodeProfiles\Webm_1080p::class,
    ];

    /** @var int */
    const TRANSCODER_TIMEOUT_SECS = 600; // 10 minutes with not progress

    /** @var Repository */
    private $repository;

    /** @var QueueDelegate */
    private $queueDelegate;

    /** @var TranscodeStorage\TranscodeStorageInterface */
    private $transcodeStorage;

    /** @var NotificationDelegate */
    private $notificationDelegate;

    /** @var MetadataDelegate */
    private $metadataDelegate;

    public function __construct($repository = null, $queueDelegate = null, $transcodeStorage = null, $notificationDelegate = null, $metadataDelegate = null)
    {
        $this->repository = $repository ?? new Repository();
        $this->queueDelegate = $queueDelegate ?? new QueueDelegate();
        $this->transcodeStorage = $transcodeStorage ?? new TranscodeStorage\S3Storage();
        $this->notificationDelegate = $notificationDelegate ?? new NotificationDelegate();
        $this->metadataDelegate = $metadataDelegate ?? new MetadataDelegate();
    }

    /**
     * Return a list of transcodes
     * @return Response
     */
    public function getList($opts): ?Response
    {
        $opts = array_merge([
            'guid' => null,
            'profileId' => null,
            'status' => null,
            'legacyPolyfill' => false,
        ], $opts);

        $response = $this->repository->getList($opts);

        if ($opts['legacyPolyfill'] && !$response->count()) {
            $response = $this->getLegacyPolyfill($opts);
        }

        return $response;
    }

    /**
     * Return a list of legacy transcodes by reading from storage
     * @param array
     * @return Response
     */
    private function getLegacyPolyfill(array $opts): ?Response
    {
        $files = $this->transcodeStorage->ls($opts['guid']);
        if (!$files) {
            return null;
        }

        $response = new Response();

        foreach ($files as $fileName) {
            // Loop through each profile to see if fileName is a match
            foreach (self::TRANSCODE_PROFILES as $profile) {
                $profile = new $profile();
                if ($profile->getStorageName() && strpos($fileName, $profile->getStorageName()) !== false) {
                    $transcode = new Transcode();
                    $transcode
                        ->setGuid($opts['guid'])
                        ->setProfile($profile)
                        ->setStatus(TranscodeStates::COMPLETED);
                    $response[] = $transcode;
                }
            }
        }
        return $response;
    }

    /**
     * Return transcodes for a video by urn
     * @param string $urn
     * @return Transcodes[]
     */
    public function getTranscodesByUrn(string $urn): array
    {
        return [];
    }

    /**
     * Upload the source file to storage
     * Note: This does not register any transcodes. createTranscodes should be called
     * @param Video $video
     * @param string $path
     * @return bool
     */
    public function uploadSource(Video $video, string $path): bool
    {
        // Upload the source file to storage
        $source = new Transcode();
        $source
            ->setVideo($video)
            ->setProfile(new TranscodeProfiles\Source());
        return (bool) $this->transcodeStorage->add($source, $path);
    }

    /**
     * This will return a url that can be used by an HTTP client
     * to upload the source file
     * @param Video $video
     * @return string
     */
    public function getClientSideUploadUrl(Video $video): string
    {
        $source = new Transcode();
        $source
            ->setVideo($video)
            ->setProfile(new TranscodeProfiles\Source());
        return $this->transcodeStorage->getClientSideUploadUrl($source);
    }

    /**
     * Create the transcodes from from
     * @param Video $video
     * @return void
     */
    public function createTranscodes(Video $video): void
    {
        foreach (self::TRANSCODE_PROFILES as $profile) {
            try {
                $transcode = new Transcode();
                $transcode
                    ->setVideo($video)
                    ->setProfile(new $profile)
                    ->setStatus(TranscodeStates::CREATED);
                // Add the transcode to database and queue
                $this->add($transcode);
            } catch (TranscodeProfiles\UnavailableTranscodeProfileException $e) {
                continue; // Silently fail and just skip
            }
        }
    }

    /**
     * Add transcode to the queue
     * @param Transcode $transcode
     * @return void
     */
    public function add(Transcode $transcode, $sendToQueue = true): void
    {
        // Add to repository
        $this->repository->add($transcode);

        if ($sendToQueue) {
            // Notify the background queue
            $this->queueDelegate->onAdd($transcode);
        }
    }

    /**
     * Update the transcode entity
     * @param Transcode $transcode
     * @param array $dirty
     * @return bool
     */
    public function update(Transcode $transcode, array $dirty = []): bool
    {
        return $this->repository->update($transcode, $dirty);
    }

    /**
     * @deprecated
     * Run the transcoder (this is called from Core\QueueRunner\Transcode hook)
     * @param Transcode $transcode
     * @return void
     */
    public function transcode(Transcode $transcode): void
    {
        throw new DeprecatedException();
    }
}
