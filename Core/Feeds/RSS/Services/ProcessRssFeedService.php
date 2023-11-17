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
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;

class ProcessRssFeedService
{
    public function __construct(
        private readonly Reader $reader,
        private readonly MetascraperService $metaScraperService,
        private readonly ActivityManager $activityManager,
        private readonly ACL $acl,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param RssFeed $rssFeed
     * @param string|null $etagOffset
     * @param string|null $lastModifiedOffset
     * @return iterable|int
     * @throws RssFeedFailedFetchException
     */
    public function fetchFeed(
        RssFeed $rssFeed,
        ?string $etagOffset = null,
        ?string $lastModifiedOffset = null
    ): iterable|int {
        try {
            $feed = $this->reader->import(
                $rssFeed->url,
                $etagOffset,
                $lastModifiedOffset
            );
        } catch (Exception $e) {
            throw new RssFeedFailedFetchException();
        }

        foreach ($feed as $entry) {
            yield $entry;
        }
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
     * @param Atom|EntryInterface|Rss $entry
     * @param User $user
     * @return bool
     */
    public function processActivitiesForFeed(
        Atom|EntryInterface|Rss $entry,
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

            $activity
                ->setTitle($richEmbed['meta']['title'])
                ->setBlurb($richEmbed['meta']['description'])
                ->setURL($link)
                ->setThumbnail($richEmbed['links']['thumbnail'][0]['href']);

            $this->activityManager->add($activity);
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
