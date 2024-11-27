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
use Minds\Core\Feeds\RSS\Exceptions\RssFeedFailedFetchException;
use Minds\Core\Feeds\RSS\Repositories\RssImportsRepository;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Log\Logger;
use Minds\Core\Media\Audio\AudioService;
use Minds\Core\Security\ACL;
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
        private readonly AudioService $audioService,
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
            $richEmbed = $this->metaScraperService->scrape($link);

            $canonicalUrl = $richEmbed['meta']['canonical_url'] ?? $link;

            // Check to see if there has been an activity
            if ($this->rssImportsRepository->hasMatch($feedId, $canonicalUrl)) {
                return false;
            }

            if (str_starts_with($entry->getEnclosure()?->type ?? '', 'audio') && $entry->getEnclosure()?->ur) {
                $audioEntity = $this->audioService->onRemoteFileUrlProvided(
                    owner: $user,
                    url: $entry->getEnclosure()->url
                );
                $activity->setEntityGuid($audioEntity->guid)
                    ->setAttachments([ $audioEntity ])
                    ->setTitle($richEmbed['meta']['title'])
                    ->setMessage($richEmbed['meta']['description']);
            } else {
                $activity
                    ->setLinkTitle($richEmbed['meta']['title'])
                    ->setBlurb($richEmbed['meta']['description'])
                    ->setURL($canonicalUrl)
                    ->setThumbnail($richEmbed['links']['thumbnail'][0]['href']);
            }

            $this->activityManager->add($activity);

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
}
