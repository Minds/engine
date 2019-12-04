<?php
/**
 * Transcoder manager
 */
namespace Minds\Core\Media\Video\Transcoder;

use Minds\Core\Media\Video\Transcoder\Delegates\QueueDelegate;
use Minds\Entities\Video;
use Minds\Traits\MagicAttributes;

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

    /** @var Repository */
    private $repository;

    /** @var QueueDelegate */
    private $queueDelegate;

    /** @var TranscodeStorage\TranscodeStorageInterface */
    private $transcodeStorage;

    /** @var TranscodeExecutors\TranscodeExecutorInterfsce */
    private $transcodeExecutor;

    public function __construct($repository = null, $queueDelegate = null, $transcodeStorage = null, $transcodeExecutor = null)
    {
        $this->repository = $repository ?? new Repository();
        $this->queueDelegate = $queueDelegate ?? new QueueDelegate();
        $this->transcodeStorage = $transcodeStorage ?? new TranscodeStorage\S3Storage();
        $this->transcodeExecutor = $transcodeExecutor ?? new TranscodeExecutors\FFMpegExecutor();
    }

    /**
     * Return a list of transcodes
     * @return Response
     */
    public function getList($opts): Response
    {
        $opts = array_merge([
            'guid' => null,
            'profileId' => null,
            'status' => null,
        ], $opts);

        return $this->repository->getList($opts);
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
                    ->setProfile(new $profile);
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
    public function add(Transcode $transcode): void
    {
        // Add to repository
        $this->repository->add($transcode);

        // Notify the background queue
        $this->queueDelegate->onAdd($transcode);
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
     * Run the transcoder (this is called from Core\QueueRunner\Transcode hook)
     * @param Transcode $transcode
     * @return void
     */
    public function transcode(Transcode $transcode): void
    {
        // Update the background so everyone knows this is inprogress
        $transcode->setStatus('transcoding');
        $this->update($transcode, [ 'status' ]);

        // Perform the transcode
        try {
            $ref = $this;
            $success = $this->transcodeExecutor->transcode($transcode, function ($progress) use ($ref) {
                $transcode->setProgress($pct);
                $this->update($transcode, 'progress');
            });
            if (!$success) { // This is actually unkown as an exception should have been thrown
                throw new TranscodeExecutors\FailedTranscodeException();
            }
            $transcode->setStatus('completed');
        } catch (TranscodeExecutors\FailedTranscodeException $e) {
            $transcode->setStatus('failed');
        } finally {
            $this->update($transcode, [ 'progress', 'status' ]);
        }

        // Was this the last transcode to complete?
        // if ($this->isLastToTrancode($transcode)) {
        //     // Sent a notification to the user saying the transcode is completed
        // }
    }

    // protected function isLastToTrancode(Transcode $transcode): bool
    // {

    // }
}
