<?php

namespace Minds\Core\Feeds\Seen;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Di\Di;

class Manager
{
    private const CACHE_KEY_PREFIX = "seen-entities";

    public function __construct(
        private ?Redis $redisClient = null,
    ) {
        $this->redisClient = $this->redisClient ?? Di::_()->get("Cache\Redis");
    }

    /**
     * Marks an array of entities as seen
     * @param string[] $entityGuids
     * @return void
     */
    public function seeEntities(array $entityGuids): void
    {
        $this->redisClient?->set(
            $this->getCacheKey(),
            array_merge(
                $this->listSeenEntities(),
                $entityGuids
            )
        );
    }

    /**
     * Returns seen entities
     * @return string[]
     */
    public function listSeenEntities(): array
    {
        $cacheKey = $this->getCacheKey();

        $data = $this->redisClient->get($cacheKey);
        return !$data ? [] : $data;
    }

    private function createSeenCacheKeyCookie(): SeenCacheKeyCookie
    {
        return (new SeenCacheKeyCookie())->createCookie();
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . ((new PseudonymousIdentifier())->getId() ?? $this->createSeenCacheKeyCookie()->getValue());
    }
}
