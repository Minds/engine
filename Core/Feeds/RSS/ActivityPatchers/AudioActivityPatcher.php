<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\ActivityPatchers;

use Laminas\Feed\Reader\Entry\AbstractEntry;
use Minds\Core\Media\Audio\AudioService;
use Minds\Entities\Activity;
use Minds\Entities\User;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioEntity;
use Minds\Core\Media\MediaDownloader\MediaDownloaderInterface;

/**
 * Patches an activity for an audio RSS entry.
 */
class AudioActivityPatcher implements RssActivityPatcherInterface
{
    public function __construct(
        private readonly AudioService $audioService,
        private readonly MediaDownloaderInterface $imageDownloader,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Patch an activity for an audio RSS entry.
     * @param Activity $activity - The base activity to patch.
     * @param EntryInterface $entry - The RSS entry.
     * @param User $user - The user.
     * @param array $richEmbedData - The rich embed data.
     * @return Activity - The patched activity.
     */
    public function patch(
        Activity $activity,
        EntryInterface $entry,
        User $owner,
        ?array $richEmbedData = null,
    ): Activity {
        $podcastImage = null;
        $podcastSummary = null;
        $podcastSubtitle = null;
        $podcastTitle = null;

        if (
            $entry instanceof AbstractEntry &&
            $podcast = $entry->getExtensions()['Podcast\Entry'] ?? false
        ) {
            $podcastImage = $podcast->getItunesImage();
            $podcastSummary = $podcast->getSummary();
            $podcastTitle = $podcast->getTitle();
            $podcastSubtitle = $podcast->getSubtitle();
        }

        $title = $this->selectDisplayFriendlyString([
            $podcastTitle,
            $entry->getTitle(),
            $richEmbedData['meta']['title'] ?? null,
            'Untitled'
        ]);

        $description = $this->selectDisplayFriendlyString([
            $podcastSubtitle,
            $podcastSummary,
            $entry->getDescription(),
            $richEmbedData['meta']['description'] ?? ''
        ]);

        $thumbnailUrl = $podcastImage ?: $richEmbedData['links']['thumbnail'][0]['href'] ?: null;

        $audioEntity = $this->handleRemoteFileUrl($owner, $entry);

        if ($thumbnailUrl) {
            $this->handleThumbnailUpload($audioEntity, $thumbnailUrl);
        }

        $activity
            ->setEntityGuid($audioEntity->guid)
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
            if (!($thumbnailBlob = $this->getThumbnailBlob($thumbnailUrl))) {
                return;
            }

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
            $imageResponse = $this->imageDownloader->download($url);
            $imageData = $imageResponse?->getBody()?->getContents();

            if (!$imageData) {
                throw new \Exception('Failed to get image data');
            }

            $contentType = $imageResponse?->getHeader('Content-Type')[0] ?? '';
            return 'data:' . $contentType . ';base64,' . base64_encode($imageData);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }

    /**
     * Selects the most suitable value from an array of values. This exists because
     * RSS fields may contain HTML. If it does, we cannot render it, so we need to
     * try the next value. Array order will dictate order of preference.
     * @param array<string> $values - Candidate values to select from.
     * @return string|null - The selected value, trimmed.
     */
    private function selectDisplayFriendlyString(array $values): ?string
    {
        foreach ($values as $value) {
            if (!is_string($value)) {
                continue;
            }

            $value = strip_tags($value);

            if (!$value || !trim($value)) {
                continue;
            }

            return trim($value);
        }

        return null;
    }
}
