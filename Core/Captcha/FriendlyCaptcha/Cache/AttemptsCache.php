<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Cache;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

/**
 * Caches amount of solve attempts - values are used to scale puzzle difficulty.
 */
class AttemptsCache
{
    // Base for cache key.
    const CACHE_KEY_BASE = 'friendly-captcha-attempts:%s';

    // Storage time in whole seconds - 1 hour.
    const CACHE_TIME_SECONDS = 3600;

    /**
     * Constructor.
     * @param ?PsrWrapper $cache - PsrWrapper around cache.
     */
    public function __construct(
        private ?PsrWrapper $cache = null,
    ) {
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
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
        $count = $this->cache->get($cacheKey);

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
        return sprintf(self::CACHE_KEY_BASE, $this->getIpHash());
    }

    /**
     * Gets IP hash from server super-global.
     * @return string - hash of ip.
     */
    private function getIpHash(): string
    {
        $ip = null;

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        }
        //whether ip is from proxy
        elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        }
        //whether ip is from remote address
        else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return hash('sha256', $ip);
    }
}
