<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Cache;

use Minds\Common\IpAddress;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

/**
 * Caches amount of solve attempts - values are used to scale puzzle difficulty.
 */
class AttemptsCache
{
    /** @var string Base for cache key. */
    const CACHE_KEY_BASE = 'friendly-captcha-attempts:%s';

    /** @var int Storage time in whole seconds - 1 day. */
    const CACHE_TIME_SECONDS = 86400;

    /**
     * Constructor.
     * @param ?PsrWrapper $cache - PsrWrapper around cache.
     * @param ?IpAddress $ipAddress - Helper used to get IP hash.
     */
    public function __construct(
        private ?PsrWrapper $cache = null,
        private ?IpAddress $ipAddress = null
    ) {
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
        $this->ipAddress ??= new IpAddress();
    }

    /**
     * Gets count of solve attempts.
     * @return int count of solve attempts.
     */
    public function getCount(): int
    {
        return intval($this->cache->get(
            $this->getCacheKey()
        )) ?? 0;
    }

    /**
     * Increment attempts count.
     * @return self
     */
    public function increment(): self
    {
        $cacheKey = $this->getCacheKey();
        $count = $this->getCount();

        if ($count > 0) {
            $this->cache->set(
                $cacheKey,
                ++$count,
                self::CACHE_TIME_SECONDS
            );
            return $this;
        }

        $this->cache->set(
            $this->getCacheKey(),
            1,
            self::CACHE_TIME_SECONDS
        );
        return $this;
    }

    /**
     * Gets cache key by interpolating IP Hash with cache key base.
     * @return string - cache key.
     */
    private function getCacheKey(): string
    {
        return sprintf(self::CACHE_KEY_BASE, $this->ipAddress->getHash());
    }
}
