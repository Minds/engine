<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Data\cache\PsrWrapper;

/**
 * Cache service to store whether the user has an active site membership.
 */
class HasActiveSiteMembershipCacheService
{
    /** Base of cache key. */
    const CACHE_KEY_BASE = 'has_site_membership:';

    public function __construct(
        private readonly PsrWrapper $cache
    ) {}

    /**
     * Get the value from cache.
     * @param int $userGuid - user GUID.
     * @return bool|null
     */
    public function get(int $userGuid): ?bool {
        $cachedValue = $this->cache->get($this->buildCacheKey($userGuid));
        return in_array($cachedValue, [1, 0], true) ? $cachedValue === 1 : null;
    }

    /**
     * Set the value in cache.
     * @param int $userGuid - user GUID.
     * @param bool $value - value to set.
     * @param int|null $ttl - TTL in seconds.
     * @return void
     */
    public function set(int $userGuid, bool $value, ?int $ttl): void {
        $this->cache->set(
            key: $this->buildCacheKey($userGuid),
            value: $value ? 1 : 0,
            ttl: $ttl
        );
    }

    /**
     * Delete the value from cache.
     * @param integer $userGuid - user GUID.
     * @return void
     */
    public function delete(int $userGuid): void {
        $this->cache->delete($this->buildCacheKey($userGuid));
    }

    /**
     * Build the cache key.
     * @param int $userGuid - user GUID.
     * @return string - cache key.
     */
    private function buildCacheKey(int $userGuid): string {
        return self::CACHE_KEY_BASE . $userGuid;
    }
}
