<?php
namespace Minds\Core\Captcha\FriendlyCaptcha\Cache;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;

/**
 * Used to cache a puzzle to ensure that multiple submissions of the same
 * puzzle are not valid.
 */
class PuzzleCache
{
    /** @var string Base for cache key. */
    const CACHE_KEY_BASE = 'friendly-captcha-puzzle:%s';

    /** @var int Storage time in whole seconds - 2 weeks. */
    const CACHE_TIME_SECONDS = 1209600;

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
     * Gets from puzzle cache as bool.
     * @param string $puzzle - puzzle to check.
     * @return boolean - true if cached value exists already.
     */
    public function get(string $puzzle): bool
    {
        return !!$this->cache->get(
            $this->getCacheKey($puzzle)
        );
    }

    /**
     * Sets puzzle in cache - marking it as already used.
     * @param string $puzzle - puzzle value.
     * @return self
     */
    public function set(string $puzzle): self
    {
        $this->cache->set(
            $this->getCacheKey($puzzle),
            1,
            self::CACHE_TIME_SECONDS
        );
        return $this;
    }

    /**
     * Gets cache key by interpolating puzzle with cache key base.
     * @return string - cache key.
     */
    private function getCacheKey(string $puzzle): string
    {
        return sprintf(self::CACHE_KEY_BASE, $puzzle);
    }
}
