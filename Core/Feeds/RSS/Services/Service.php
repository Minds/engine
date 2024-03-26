<?php
declare(strict_types=1);

namespace Minds\Core\Feeds\RSS\Services;

use Exception;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Feeds\RSS\Enums\RssFeedLastFetchStatusEnum;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedFailedFetchException;
use Minds\Core\Feeds\RSS\Exceptions\RssFeedNotFoundException;
use Minds\Core\Feeds\RSS\Repositories\RssFeedsRepository;
use Minds\Core\Feeds\RSS\Repositories\RssImportsRepository;
use Minds\Core\Feeds\RSS\Types\RssFeed;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use Zend\Diactoros\Uri;

class Service
{
    public function __construct(
        private readonly ProcessRssFeedService  $processRssFeedService,
        private readonly RssFeedsRepository     $rssFeedsRepository,
        private readonly MultiTenantBootService $multiTenantBootService,
        private readonly EntitiesBuilder        $entitiesBuilder,
        private readonly Logger                 $logger
    ) {
    }

    /**
     * @param RssFeed $rssFeed
     * @param User $user
     * @return RssFeed
     * @throws ServerErrorException
     * @throws RssFeedFailedFetchException
     * @throws GraphQLException
     */
    public function createRssFeed(RssFeed $rssFeed, User $user): RssFeed
    {
        try {
            $rssFeedDetails = $this->processRssFeedService->getFeedDetails($rssFeed->url);
            return $this->rssFeedsRepository->createRssFeed(
                rssFeedUrl: new Uri($rssFeed->url),
                title: $rssFeedDetails->getTitle(),
                user: $user
            );
        } catch (RssFeedFailedFetchException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new GraphQLException($e->getMessage(), 500);
        }
    }

    /**
     * @param int $feedId
     * @return RssFeed
     * @throws RssFeedNotFoundException
     * @throws ServerErrorException
     * @throws RssFeedFailedFetchException
     * @throws GraphQLException
     */
    public function getRssFeed(int $feedId, User $user): RssFeed
    {
        $rssFeed = $this->rssFeedsRepository->getFeed($feedId);
        if ($rssFeed->userGuid !== (int)$user->getGuid()) {
            throw new GraphQLException("The feed provided does not belong to the user", 403);
        }

        $this->processRssFeedService->fetchFeed($rssFeed);
        return $rssFeed;
    }

    /**
     * @param User $user
     * @return array
     * @throws ServerErrorException
     */
    public function getRssFeeds(User $user): array
    {
        return iterator_to_array($this->rssFeedsRepository->getFeeds($user));
    }

    /**
     * @param int $feedId
     * @param User $user
     * @return bool
     * @throws GraphQLException
     * @throws RssFeedNotFoundException
     * @throws ServerErrorException
     */
    public function removeRssFeed(
        int  $feedId,
        User $user
    ): bool {
        $rssFeed = $this->rssFeedsRepository->getFeed($feedId);
        if ($rssFeed->userGuid !== (int)$user->getGuid()) {
            throw new GraphQLException("The feed provided does not belong to the user", 403);
        }

        return $this->rssFeedsRepository->removeRssFeed($feedId);
    }

    /**
     * @throws ServerErrorException
     * @throws RssFeedNotFoundException
     * @throws GraphQLException
     */
    public function refreshRssFeed(
        int  $feedId,
        User $user,
    ): RssFeed {
        $rssFeed = $this->rssFeedsRepository->getFeed($feedId);
        if ($rssFeed->userGuid !== (int)$user->getGuid()) {
            throw new GraphQLException("The feed provided does not belong to the user", 403);
        }

        if ($rssFeed->lastFetchStatus === RssFeedLastFetchStatusEnum::FETCH_IN_PROGRESS) {
            throw new GraphQLException("The feed is already being refreshed", 403);
        }

        $this->processRssFeed(
            rssFeed: $rssFeed,
            user: $user
        );

        return $this->rssFeedsRepository->getFeed($feedId);
    }

    /**
     * Processess a single rss feed and interates through its items
     */
    public function processRssFeed(
        RssFeed $rssFeed,
        User    $user,
        bool    $dryRun = false
    ): void {
        if ($rssFeed->lastFetchStatus === RssFeedLastFetchStatusEnum::FETCH_IN_PROGRESS) {
            $this->logger->info('Skipping RSS feed as it is already being processed', [
                'feed_id' => $rssFeed->feedId,
                'url' => $rssFeed->url,
                'tenant' => $rssFeed->tenantId,
            ]);
            return;
        }

        if (!$dryRun) {
            $this->rssFeedsRepository->updateRssFeedStatus($rssFeed->feedId, RssFeedLastFetchStatusEnum::FETCH_IN_PROGRESS);
        }

        $this->logger->info('Processing RSS feed', [
            'feed_id' => $rssFeed->feedId,
            'url' => $rssFeed->url,
            'tenant' => $rssFeed->tenantId,
        ]);

        $status = RssFeedLastFetchStatusEnum::SUCCESS;

        try {
            foreach ($this->processRssFeedService->fetchFeed(rssFeed: $rssFeed) as $entry) {
                $entryTimestamp = $entry->getDateModified()?->getTimestamp() ?? $entry->getDateCreated()?->getTimestamp();

                if (!$entryTimestamp) {
                    $this->logger->info("Skipping entry {$entry->getTitle()} as it has no timestamp");
                    continue;
                }

                if ($entryTimestamp <= $rssFeed->lastFetchAtTimestamp) {
                    $this->logger->info("Skipping entry {$entry->getTitle()} as it is older than last fetch timestamp");
                    continue;
                }

                if ($entryTimestamp < strtotime('-1 hour')) {
                    $this->logger->info("Skipping entry {$entry->getTitle()} as it is older than 1 hour");
                    continue;
                }

                if ($dryRun) {
                    continue;
                }

                $this->processRssFeedService->processActivity(
                    entry: $entry,
                    feedId: $rssFeed->feedId,
                    user: $user,
                );
            }
        } catch (RssFeedFailedFetchException $e) {
            $status = RssFeedLastFetchStatusEnum::FAILED_TO_CONNECT;
            $this->logger->error($e->getMessage());
        } catch (Exception $e) {
            $status = RssFeedLastFetchStatusEnum::FAILED_TO_PARSE;
            $this->logger->error($e->getMessage());
        } finally {
            $this->logger->info('Finished processing RSS feed', [
                'feed_id' => $rssFeed->feedId,
                'url' => $rssFeed->url,
                'tenant' => $rssFeed->tenantId,
            ]);
            $this->logger->info('Updating RSS feed last fetch status');
            if ($dryRun) {
                $this->logger->info('Dry run, not updating last fetch status');
            } else {
                $this->rssFeedsRepository->updateRssFeed($rssFeed->feedId, null, $status);
            }
        }
    }

    /**
     * @param bool $dryRun
     * @return void
     * @throws NoTenantFoundException
     * @throws ServerErrorException
     */
    public function processFeeds(bool $dryRun = false): void
    {
        $currentUser = null;
        foreach ($this->rssFeedsRepository->getFeeds() as $rssFeed) {
            if ($rssFeed->tenantId) {
                $this->multiTenantBootService->bootFromTenantId($rssFeed->tenantId);
            }

            if (!$currentUser || (int)$currentUser->getGuid() !== $rssFeed->userGuid) {
                $currentUser = $this->entitiesBuilder->single($rssFeed->userGuid);
            }

            if (!$currentUser instanceof User) {
                continue;
            }

            $this->processRssFeed($rssFeed, $currentUser, $dryRun);

            if ($rssFeed->tenantId) {
                $this->multiTenantBootService->resetRootConfigs();
            }
        }
    }
}
