<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Cache;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Log\Logger;

class BoostFeedCache
{
    /** @var string */
    private const CACHE_KEY_PREFIX = 'boost-feed';

    /** @var int */
    private const TTL_SECONDS = 1;

    /** @var string */
    private const BOOSTS_KEY = 'boosts';

    /** @var string */
    private const HAS_NEXT_KEY = 'hasNext';

    public function __construct(
        private PsrWrapper $cache,
        private Logger $logger
    ) {
    }

    /**
     * Get boost feed from cache.
     * @param int $limit - limit.
     * @param int $offset - offset.
     * @param ?int $targetStatus - target status.
     * @param bool $forApprovalQueue - for approval queue.
     * @param ?string $targetUserGuid - target user guid.
     * @param bool $orderByRanking - order by ranking.
     * @param int $targetAudience - target audience.
     * @param ?int $targetLocation - target location.
     * @param ?string $loggedInUserGuid - logged in user guid.
     * @param bool &$hasNext - has next, passed by reference.
     * @return array|null - boost feed.
     */
    public function get(
        int $limit,
        int $offset,
        ?int $targetStatus,
        bool $forApprovalQueue,
        ?string $targetUserGuid,
        bool $orderByRanking,
        int $targetAudience,
        ?int $targetLocation,
        ?string $loggedInUserGuid,
        bool &$hasNext = null
    ): ?array {
        try {
            $cachedValue = $this->cache->get(
                key: $this->buildCacheKey(
                    limit: $limit,
                    offset: $offset,
                    targetStatus: $targetStatus,
                    forApprovalQueue: $forApprovalQueue,
                    targetUserGuid: $targetUserGuid,
                    orderByRanking: $orderByRanking,
                    targetAudience: $targetAudience,
                    targetLocation: $targetLocation,
                    loggedInUserGuid: $loggedInUserGuid,
                )
            );
            
            $unserializedValue = $cachedValue ?
                unserialize($cachedValue) :
                null;

            if (!$unserializedValue || !$unserializedValue[self::BOOSTS_KEY]) {
                return null;
            }

            // Passed by reference - so that cached values paginate.
            $hasNext = $unserializedValue[self::HAS_NEXT_KEY] ?? false;
            return $unserializedValue[self::BOOSTS_KEY] ?? null;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Set boost feed in cache.
     * @param int $limit - limit.
     * @param int $offset - offset.
     * @param ?int $targetStatus - target status.
     * @param bool $forApprovalQueue - for approval queue.
     * @param ?string $targetUserGuid - target user guid.
     * @param bool $orderByRanking - order by ranking.
     * @param int $targetAudience - target audience.
     * @param ?int $targetLocation - target location.
     * @param ?string $loggedInUserGuid - logged in user guid.
     * @param array $boosts - boost feed.
     * @param bool $hasNext - has next.
     * @return bool
     */
    public function set(
        int $limit,
        int $offset,
        ?int $targetStatus,
        bool $forApprovalQueue,
        ?string $targetUserGuid,
        bool $orderByRanking,
        int $targetAudience,
        ?int $targetLocation,
        ?string $loggedInUserGuid,
        array $boosts,
        bool $hasNext
    ): bool {
        try {
            return $this->cache->set(
                key: $this->buildCacheKey(
                    limit: $limit,
                    offset: $offset,
                    targetStatus: $targetStatus,
                    forApprovalQueue: $forApprovalQueue,
                    targetUserGuid: $targetUserGuid,
                    orderByRanking: $orderByRanking,
                    targetAudience: $targetAudience,
                    targetLocation: $targetLocation,
                    loggedInUserGuid: $loggedInUserGuid,
                ),
                value: serialize([
                    self::BOOSTS_KEY => $boosts,
                    self::HAS_NEXT_KEY => $hasNext
                ]),
                ttl: self::TTL_SECONDS
            );
        } catch (\Exception $e) {
            $this->logger->error($e);
            return false;
        }
    }

    /**
     * Build cache key.
     * @param int $limit - limit.
     * @param int $offset - offset.
     * @param ?int $targetStatus - target status.
     * @param bool $forApprovalQueue - for approval queue.
     * @param ?string $targetUserGuid - target user guid.
     * @param bool $orderByRanking - order by ranking.
     * @param int $targetAudience - target audience.
     * @param ?int $targetLocation - target location.
     * @param ?string $loggedInUserGuid - logged in user guid.
     * @return string cache key.
     */
    public function buildCacheKey(
        int $limit,
        int $offset,
        ?int $targetStatus,
        bool $forApprovalQueue,
        ?string $targetUserGuid,
        bool $orderByRanking,
        int $targetAudience,
        ?int $targetLocation,
        ?string $loggedInUserGuid,
    ): string {
        return self::CACHE_KEY_PREFIX . ':' . md5(implode(':', [
            $limit,
            $offset,
            $targetStatus,
            is_bool($forApprovalQueue) && !is_null($forApprovalQueue) ? (int) $forApprovalQueue : null, // boolean to string or null
            $targetUserGuid,
            is_bool($orderByRanking) && !is_null($orderByRanking) ? (int) $orderByRanking : null, // boolean to string or null
            $targetAudience,
            $targetLocation,
            $loggedInUserGuid,
        ]));
    }
}
