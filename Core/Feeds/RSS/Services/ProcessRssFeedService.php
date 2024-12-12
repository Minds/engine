<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Services;

use Exception;
use GuzzleHttp\Exception\ClientException;
use Laminas\Feed\Reader\Entry\EntryInterface;
use Laminas\Feed\Reader\Entry\Rss;
use Laminas\Feed\Reader\Feed\Atom as FeedAtom;
use Laminas\Feed\Reader\Feed\FeedInterface;
use Laminas\Feed\Reader\Feed\Rss as FeedRss;
use Laminas\Feed\Reader\Reader;
use Laminas\Feed\Writer\Renderer\Entry\Atom;
use Minds\Common\Access;
use Minds\Core\Feeds\Activity\Manager as ActivityManager;
use Minds\Core\Feeds\Activity\RichEmbed\Metascraper\Service as MetascraperService;
use Minds\Core\Feeds\RSS\ActivityPatchers\AudioActivityPatcher;
use Minds\Core\Feeds\RSS\ActivityPatchers\RssActivityPatcherInterface;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedFailedFetchException;
use Minds\Core\Feeds\RSS\Repositories\RssImportsRepository;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Security\ACL;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Services\RbacGatekeeperService;
use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;

class ProcessRssFeedService
{
    public function __construct(
        private readonly ReaderLibraryWrapper $reader,
        private readonly MetascraperService $metaScraperService,
        private readonly ActivityManager $activityManager,
        private readonly RssImportsRepository $rssImportsRepository,
        private readonly AudioActivityPatcher $audioActivityPatcher,
        private readonly AudioService $audioService,
        private readonly RbacGatekeeperService $rbacGatekeeperService,
        private readonly ACL $acl,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param RssFeed $rssFeed
     * @return EntryInterface[]
     * @throws RssFeedFailedFetchException
     */
    public function fetchFeed(
        RssFeed $rssFeed
    ): array {
        try {
            $feed = $this->reader->import(
                $rssFeed->url
            );
        } catch (Exception $e) {
            throw new RssFeedFailedFetchException();
        }

        return iterator_to_array($feed);
    }

    /**
     * @param string $url
     * @return FeedInterface|FeedAtom|FeedRss
     * @throws RssFeedFailedFetchException
     */
    public function getFeedDetails(string $url): FeedInterface|FeedAtom|FeedRss
    {
        try {
            return $this->reader->import($url);
        } catch (Exception $e) {
            throw new RssFeedFailedFetchException();
        }
    }

    /**
     * Imports activity posts for a single rss feed entry
     */
    public function processActivity(
        EntryInterface $entry,
        int $feedId,
        User $user
    ): bool {
        $link = $entry->getLink() ?? $entry->getEnclosure()?->url ?? $entry->getPermalink();
        if (!$link) {
            return true;
        }
        $ia = $this->acl->setIgnore(true);
        $activity = $this->prepareMindsActivity($user);
        try {
            $richEmbed = $this->getRichEmbedData($link);
            $canonicalUrl = $richEmbed['meta']['canonical_url'] ?? $link;

            // Check to see if there has been an activity
            if ($this->rssImportsRepository->hasMatch($feedId, $canonicalUrl)) {
                return false;
            }

            $audioEntity = null;

            if (
                $entry->getEnclosure()?->url &&
                str_starts_with($entry->getEnclosure()?->type ?? '', 'audio') &&
                $this->rbacGatekeeperService->isAllowed(PermissionsEnum::CAN_UPLOAD_AUDIO, $user, false)
            ) {
                $activity = $this->audioActivityPatcher->patch(
                    activity: $activity,
                    entry: $entry,
                    owner: $user,
                    richEmbedData: $richEmbed,
                    audioEntity: $audioEntity, // pass by reference
                );
            } else {
                if (!$richEmbed) {
                    return false;
                }

                $activity
                    ->setLinkTitle($richEmbed['meta']['title'])
                    ->setBlurb($richEmbed['meta']['description'])
                    ->setURL($canonicalUrl)
                    ->setThumbnail($richEmbed['links']['thumbnail'][0]['href']);
            }

            $this->activityManager->add($activity);

            if ($audioEntity) {
                $this->activityManager->patchAttachmentEntity($activity, $audioEntity);
            }

            // Save this activity to our database so we don't import it again
            $this->rssImportsRepository->addEntry($feedId, $canonicalUrl, (int) $activity->getGuid());
        } catch (ClientException|Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        } finally {
            $this->acl->setIgnore($ia);
        }
        return true;
    }

    private function prepareMindsActivity(User $user): Activity
    {
        $activity = new Activity();
        $activity->setAccessId(Access::PUBLIC);
        $activity->setSource(FederatedEntitySourcesEnum::LOCAL);

        $activity->container_guid = $user->guid;
        $activity->owner_guid = $user->guid;
        $activity->ownerObj = $user->export();

        return $activity;
    }

    /**
     * Gets rich embed data for a given link.
     * @param string $link - The link to get rich embed data for.
     * @return array|null - The rich embed data or null if an error occurs.
     */
    private function getRichEmbedData(string $link): ?array
    {
        try {
            return $this->metaScraperService->scrape($link);
        } catch (Exception $e) {
            $this->logger->error($e->getMessage());
            return null;
        }
    }
}
