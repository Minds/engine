<?php
namespace Minds\Core\Feeds\RSS\ActivityBuilders;

use Minds\Core\Media\Audio\AudioService;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\MediaDownloader\MediaDownloaderInterface;

/**
 * Builds an activity for an audio RSS entry.
 */
class AudioActivityBuilder
{
    public function __construct(
        private readonly AudioService $audioService,
        private readonly MediaDownloaderInterface $imageDownloader,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Builds an activity for an audio RSS entry.
     * @param Activity $activity - The activity to build.
     * @param EntryInterface $entry - The RSS entry.
     * @param User $user - The user.
     * @param array $richEmbedData - The rich embed data.
     * @return Activity - The built activity.
     */
    public function build(
        Activity $activity,
        EntryInterface $entry,
        User $user,
        array $richEmbedData
    ): Activity {
        if ($podcast = $entry->getExtensions()['Podcast\Entry'] ?? false) {
            $podcastImage = $podcast->getItunesImage();
            $podcastSummary = $podcast->getSummary();
            $podcastTitle = $podcast->getTitle();
        }

        // Strip tags to avoid any HTML in the title or description.
        $title = strip_tags($podcastTitle) ?:
            strip_tags($entry->getTitle()) ?:
            $richEmbedData['meta']['title'] ?:
            'Untitled';

        $description = strip_tags($podcastSummary) ?:
            strip_tags($entry->getDescription()) ?:
            $richEmbedData['meta']['description'] ?:
            null;

        $thumbnailUrl = $podcastImage ?: $richEmbedData['links']['thumbnail'][0]['href'] ?: null;

        $audioEntity = $this->handleRemoteFileUrl($user, $entry);

        if ($thumbnailUrl) {
            $this->handleThumbnailUpload($audioEntity, $thumbnailUrl);
        }

        $activity->setEntityGuid($audioEntity->guid)
            ->setAttachments([ $audioEntity ])
            ->setTitle($title)
            ->setMessage($description);

        return $activity;
    }

    /**
     * Handles the remote file URL.
     * @param User $user - The user.
     * @param EntryInterface $entry - The RSS entry.
     * @return AudioEntity - The audio entity.
     */
    private function handleRemoteFileUrl(User $user, EntryInterface $entry): AudioEntity
    {
        return $this->audioService->onRemoteFileUrlProvided(
            owner: $user,
            url: $entry->getEnclosure()->url
        );
    }

    /**
     * Handles the thumbnail upload.
     * @param AudioEntity $audioEntity - The audio entity.
     * @param string $thumbnailUrl - The thumbnail URL.
     * @return void
     */
    private function handleThumbnailUpload(AudioEntity $audioEntity, string $thumbnailUrl): void
    {
        try {
            $thumbnailBlob = $this->getThumbnailBlob($thumbnailUrl);

            $this->audioService->uploadThumbnailFromBlob(
                $audioEntity,
                $thumbnailBlob
            );
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }

    /**
     * Gets the thumbnail blob.
     * @param string $url - The thumbnail URL.
     * @return string|null - The thumbnail blob.
     */
    private function getThumbnailBlob(string $url): ?string
    {
        try {
            return $this->imageDownloader->download($url);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
