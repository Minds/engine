<?php

namespace Minds\Core\Feeds\Seen;

use Minds\Common\PseudonymousIdentifier;
use Minds\Core\Data\Redis;
use Minds\Core\Di\Di;

class Manager
{
    /** @var string */
    private const CACHE_KEY_PREFIX = "seen-entities";

    /** @var int */
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private ?Redis\Client $redisClient = null,
        private ?SeenCacheKeyCookie $seenCacheKeyCookie = null,
    ) {
        $this->redisClient = $this->redisClient ?? Di::_()->get("Redis");
        $this->seenCacheKeyCookie = $this->seenCacheKeyCookie ?? new SeenCacheKeyCookie();
    }

    /**
     * Marks an array of entities as seen
     * @param string[] $entityGuids
     * @return void
     */
    public function seeEntities(array $entityGuids): void
    {
        $this->redisClient?->sAdd(
            $this->getCacheKey(),
            ...$entityGuids
        );
        $this->redisClient?->expire($this->getCacheKey(), self::CACHE_TTL); // Expire the entire set
    }

    /**
     * Returns seen entities
     * @return string[]
     */
    public function listSeenEntities(int $limit = 100): array
    {
        $cacheKey = $this->getCacheKey();
        $cursor = null;
        $data = $this->redisClient->sScan($cacheKey, $cursor, null, $limit);
        return !$data ? [] : $data;
    }

    private function createSeenCacheKeyCookie(): SeenCacheKeyCookie
    {
        return $this->seenCacheKeyCookie->createCookie();
    }

    private function getCacheKey(): string
    {
        $pseudoId = (new PseudonymousIdentifier())->getId();
        $generatedCacheKey = $this->createSeenCacheKeyCookie()->getValue();
        return self::CACHE_KEY_PREFIX . '::' . ($pseudoId ?? $generatedCacheKey);
    }
}
