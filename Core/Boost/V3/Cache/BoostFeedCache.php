<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Cache;

use Minds\Core\Data\cache\PsrWrapper;

class BoostFeedCache
{
    /** @var string */
    private const CACHE_KEY_PREFIX = 'boost-feed';

    /** @var int */
    private const TTL_SECONDS = 60;

    public function __construct(
        private PsrWrapper $cache
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
     * @param bool &$hasNext - has next.
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
        bool &$hasNext
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

            if (!$unserializedValue || !$unserializedValue['boosts']) {
                return null;
            }

            // Passed by reference - so that cached values paginate.
            $hasNext = $unserializedValue['hasNext'] ?? false;
            return $unserializedValue['boosts'];
        } catch (\Exception $e) {
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
                'boosts' => $boosts,
                'hasNext' => $hasNext
            ]),
            ttl: self::TTL_SECONDS
        );
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
        return self::CACHE_KEY_PREFIX . ':' . implode(':', [
            $limit,
            $offset,
            $targetStatus,
            $forApprovalQueue,
            $targetUserGuid,
            $orderByRanking,
            $targetAudience,
            $targetLocation,
            $loggedInUserGuid,
        ]);
    }
}
