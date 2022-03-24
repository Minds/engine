<?php
namespace Minds\Core\Hashtags\Trending;

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

    public function __construct(private ?RedisClient $redis = null)
    {
        $this->redis = $redis ?? Di::_()->get('Redis');
    }
    
    /**
     * Sets hashtags in cache.
     * @param array $dailyTrending - array of tags to cache.
     * @return self instance of this.
     */
    public function set($dailyTrending): self
    {
        $this->redis->set(
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
        $cached = $this->redis->get(self::CACHE_KEY);
        return json_decode($cached, false) ?? [];
    }
}
