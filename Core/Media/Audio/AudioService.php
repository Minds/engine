<?php
namespace Minds\Core\Media\Audio;

use DateTimeImmutable;
use FFMpeg\FFMpeg;
use FFMpeg\FFProbe;
use FFMpeg\Format\Audio\Mp3;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Entities\User;
use Psr\SimpleCache\CacheInterface;

class AudioService
{
    private const ENTITY_CACHE_KEY_PREFIX = 'audio:entity:';
    private const DOWNLOAD_URL_CACHE_KEY_PREFIX = 'audio:download:';

    public function __construct(
        private AudioAssetStorageService $audioAssetStorageService,
        private AudioRepository $audioRepository,
        private FFMpeg $fFMpeg,
        private FFProbe $fFProbe,
        private ActionEventsTopic $actionEventsTopic,
        private CacheInterface $cache,
    ) {
        
    }

    /**
     * Returns an audio entity by its guid
     */
    public function getByGuid(int $guid): ?AudioEntity
    {
        $cacheKey = self::ENTITY_CACHE_KEY_PREFIX . $guid;

        if ($this->cache->has($cacheKey)) {
            $cached = $this->cache->get($cacheKey);
            return unserialize($cached);
        }

        $audioEntity = $this->audioRepository->getByGuid($guid);

        $this->cache->set($cacheKey, serialize($audioEntity));

        return $audioEntity;
    }

    /**
     * Return a public asset directly from the storage service
     * TODO: Cache this so we don't keep making preauth requests
     */
    public function getDownloadUrl(AudioEntity $audioEntity, string $filename = AudioAssetStorageService::RESAMPLED_FILENAME): string
    {
        $cacheKey = self::DOWNLOAD_URL_CACHE_KEY_PREFIX . $audioEntity->guid;

        if ($this->cache->has($cacheKey)) {
            return $this->cache->get($cacheKey);
        }

        $url = $this->audioAssetStorageService->getDownloadUrl($audioEntity, $filename);

        $this->cache->set($cacheKey, $url, 43200); // 12 hours

        return $url;
    }

    /**
     * Returns a url that a user can upload the audio file to directly without
     * having to hit our servers
     */
    public function getClientSideUploadUrl(AudioEntity $entity): string
    {
        return $this->audioAssetStorageService->getClientSideUploadUrl($entity);
    }

    /**
     * When an upload is initiated, store a reference in the database.
     * We may need to prune abandoned files in the future.
     */
    public function onUploadInitiated(AudioEntity $audioEntity): void
    {
        // Save a reference to the database.
        // If this fails then delete the asset
        $this->audioRepository->add($audioEntity);
    }

    /**
     * Queue the audio entity
     * The asset should have already been uploaded with the client side upload earlier
     */
    public function onUploadCompleted(AudioEntity $audioEntity, User $user): void
    {
        // If already marked as uploaded, do not proceed
        if ($audioEntity->uploadedAt) {
            throw new ForbiddenException();
        }

        // Mark as uploaded on the datastore
        $audioEntity->uploadedAt = new DateTimeImmutable('now');
        $this->audioRepository->update($audioEntity, [ 'uploadedAt' ]);

        // Clear cache
        $this->cache->destroy(self::ENTITY_CACHE_KEY_PREFIX . $audioEntity->guid);

        // Submit an event to the event stream so the workers can process in the background
        $event = new ActionEvent();
        $event->setAction(ActionEvent::ACTION_AUDIO_UPLOAD)
            ->setEntity($audioEntity)
            ->setUser($user);

        $this->actionEventsTopic->send($event);
    }

    /**
     * Process the audio file into our common format
     */
    public function processAudio(AudioEntity $audioEntity): bool
    {
        // Download the source file from s3 bucket
        $audioSrc = $this->audioAssetStorageService->download($audioEntity);

        // Output to tmp directory (need .mp3 suffix)
        $resampledMp3Filename = sys_get_temp_dir() . "/$audioEntity->guid.mp3";

        // Reprocess the file
        $audio = $this->fFMpeg->open(stream_get_meta_data($audioSrc)['uri']);
        $audio->save(
            format: (new Mp3())
                ->setAudioChannels(1) // Force to mono
                ->setAudioKiloBitrate(128),
            outputPathfile: $resampledMp3Filename
        );
    
        // Get the stats
        $format = $this->fFProbe->format(stream_get_meta_data($audioSrc)['uri']);
        $audioEntity->durationSecs = $format->get('duration');

        // Upload the asset to the filestore
        $this->audioAssetStorageService->upload($audioEntity, $resampledMp3Filename);

        // Cleanup the file
        unlink($resampledMp3Filename);

        // Update the database with the completed state (TODO: Prune abandoned audio uploads every 24 hours)
        $audioEntity->processedAt = new DateTimeImmutable('now');
        $this->audioRepository->update($audioEntity, [ 'processedAt', 'durationSecs' ]);

        // Clear cache
        $this->cache->destroy(self::ENTITY_CACHE_KEY_PREFIX . $audioEntity->guid);

        return true;
    }

    /**
     * The final step is when the activity post is actually created.
     */
    public function onActivityPostCreated(AudioEntity $audioEntity, int $activityGuid): bool
    {
        // Update the access id to be the activity guid
        $audioEntity->accessId = $activityGuid;

        $success = $this->audioRepository->updateAccessId($audioEntity);
        if ($success) {
            $this->cache->destroy(self::ENTITY_CACHE_KEY_PREFIX . $audioEntity->guid);
        }

        return $success;
    }

}
