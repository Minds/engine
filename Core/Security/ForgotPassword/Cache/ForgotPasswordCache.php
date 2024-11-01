<?php
declare(strict_types=1);

namespace Minds\Core\Security\ForgotPassword\Cache;

use Minds\Core\Di\Di;
use Psr\SimpleCache\CacheInterface;

/**
 * Cache for handling forgot password codes.
 */
class ForgotPasswordCache
{
    /** @var string Cache key prefix. */
    private const CACHE_KEY_PREFIX = 'forgot-password:';

    /** @var int TTL in seconds. */
    private const TTL = 86400; // 1 day

    public function __construct(
        private ?CacheInterface $cache = null,
    ) {
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Get the cached forgot password code for a user.
     * @param int $userGuid - The user's GUID.
     * @return string|null - The cached forgot password code.
     */
    public function get(int $userGuid): ?string
    {
        return $this->cache->get($this->buildCacheKey($userGuid)) ?: null;
    }

    /**
     * Set the forgot password code for a user in cache.
     * @param int $userGuid - The user's GUID.
     * @param string $code - The forgot password code.
     * @return bool - Whether caching was successful.
     */
    public function set(int $userGuid, string $code): bool
    {
        return $this->cache->set(
            $this->buildCacheKey($userGuid),
            $code,
            self::TTL
        );
    }

    /**
     * Delete the cached forgot password code for a user.
     * @param int $userGuid - The user's GUID.
     * @return self
     */
    public function delete(int $userGuid): self
    {
        $this->cache->delete($this->buildCacheKey($userGuid));
        return $this;
    }

    /**
     * Build the cache key for a user.
     * @param int $userGuid - The user's GUID.
     * @return string - The cache key.
     */
    private function buildCacheKey(int $userGuid): string
    {
        return self::CACHE_KEY_PREFIX . $userGuid;
    }
}
