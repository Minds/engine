<?php
namespace Minds\Core\Hashtags\Trending;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Core\Data\Redis\Client as RedisClient;
use Minds\Interfaces\BasicCacheInterface;

/**
 * Handles the caching of trending hashtags.
 */
class Cache implements BasicCacheInterface
{
    // Key for storage in the cache.
    const CACHE_KEY = 'hashtags:trending:daily';

    // Storage time in whole seconds.
    const CACHE_TIME_SECONDS = 600;

    public function __construct(private ?PsrWrapper $cache = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
    }
    
    /**
     * Sets hashtags in cache.
     * @param array $dailyTrending - array of tags to cache.
     * @return self instance of this.
     */
    public function set($dailyTrending): self
    {
        $this->cache->set(
            self::CACHE_KEY,
            json_encode($dailyTrending),
            self::CACHE_TIME_SECONDS
        );
        return $this;
    }

    /**
     * Gets cached hashtags or returns null array.
     * @return array - cached hashtags or null array.
     */
    public function get(): array
    {
        $cached = $this->cache->get(self::CACHE_KEY);
        return json_decode($cached, false) ?? [];
    }
}
