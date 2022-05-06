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
        private ?SeenCacheKeyCookie $seenCacheKeyCookie = null,
    ) {
        $this->redisClient = $this->redisClient ?? Di::_()->get("Cache\Redis");
        $this->seenCacheKeyCookie = $this->seenCacheKeyCookie ?? new SeenCacheKeyCookie();
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
        return $this->seenCacheKeyCookie->createCookie();
    }

    private function getCacheKey(): string
    {
        $pseudoId = (new PseudonymousIdentifier())->getId();
        $uniqueGeneratedCacheKey = $this->createSeenCacheKeyCookie()->getValue();

        return self::CACHE_KEY_PREFIX . ':' . ($pseudoId ?? $uniqueGeneratedCacheKey);
    }
}
