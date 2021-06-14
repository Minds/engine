<?php
namespace Minds\Core\Feeds\Activity;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Di\Di;
use Minds\Entities\Activity;
use Minds\Core\Feeds\Elastic;

class InteractionCounters
{
    /** @var string */
    const CACHE_PREFIX = 'interactions:count';

    /** @var int */
    const CACHE_TTL = 86400; // 1 day

    /** @var string */
    const COUNTER_QUOTES = 'quotes';

    /** @var PsrWrapper */
    protected $cache;

    /** @var Elastic\Manager */
    protected $feedsManager;

    /** @var string */
    protected $counter;

    public function __construct(PsrWrapper $cache = null, Elastic\Manager $feedsManager = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache\PsrWrapper');
        $this->feedsManager = $feedsManager ?? Di::_()->get('Feeds\Elastic\Manager');
    }

    /**
     * @param string $counter
     * @return self
     */
    public function setCounter(string $counter): self
    {
        switch ($counter) {
            case self::COUNTER_QUOTES:
                $this->counter = $counter;
                break;
            default:
                throw new \Exception("Invalid counter key");
        }
        return $this;
    }

    /**
     * @param Activity $activity
     * @return int
     */
    public function get(Activity $activity): int
    {
        $cacheKey = $this->buildCacheKey($activity);

        if ($count = $this->cache->get($cacheKey)) {
            return $count;
        }

        switch ($this->counter) {
            case self::COUNTER_QUOTES:
                $count = $this->feedsManager->getCount([
                    'algorithm' => 'latest',
                    'type' => 'activity',
                    'period' => 'all',
                    'quote_guid' => $activity->getGuid(),
                ]);
                break;
            default:
                $count = 0;
        }

        $this->cache->set($cacheKey, $count, self::CACHE_TTL);

        return $count;
    }

    /**
     * Purges the counter cache
     * @param Activity $activity
     * @return void
     */
    public function purgeCache(Activity $activity): void
    {
        $this->cache->delete($this->buildCacheKey($activity));
    }

    /**
     * @return string
     */
    protected function buildCacheKey(Activity $activity): string
    {
        return self::CACHE_PREFIX . ":$this->counter:{$activity->getGuid()}";
    }
}
