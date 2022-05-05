<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Exception;
use Minds\Common\PseudonymousIdentifier;
use Minds\Common\Repository\Response;
use Minds\Core\Data\Redis;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Entities\User;

class Manager implements ManagerInterface
{
    /** @var string */
    private const CACHE_KEY_PREFIX = "seen-entities";

    /** @var int */
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private ?Redis\Client $redisClient = null,
        private ?ElasticSearchManager $elasticSearchManager = null
    ) {
        $this->redisClient = $this->redisClient ?? Di::_()->get("Redis");
        $this->elasticSearchManager = $this->elasticSearchManager ?? Di::_()->get("Feeds\Elastic\Manager");
    }

    /**
     * @param User $targetUser
     * @param int $totalEntitiesToRetrieve
     * @return Response
     * @throws Exception
     */
    public function getUnseenTopEntities(
        User $targetUser,
        int $totalEntitiesToRetrieve
    ): Response {
        $queryOptions = [
            'limit' => $totalEntitiesToRetrieve,
            'type' => 'activity',
            'algorithm' => 'top',
            'subscriptions' => $targetUser->getGuid(),
            'period' => 'all' // legacy option
        ];

        $previouslySeenEntities = $this->getUserPreviouslySeenTopFeedEntitiesCacheAvailable();
        if (count($previouslySeenEntities) > 0) {
            $queryOptions['exclude'] = $previouslySeenEntities;
        }

        return $this->elasticSearchManager->getList($queryOptions);
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

    private function createUnseenTopFeedCacheKeyCookie(): UnseenTopFeedCacheKeyCookie
    {
        return (new UnseenTopFeedCacheKeyCookie())->createCookie();
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . '::' . ((new PseudonymousIdentifier())->getId() ?? $this->createUnseenTopFeedCacheKeyCookie()->getValue());
    }

    /**
     * @param int $limit
     * @return string[]
     */
    private function getUserPreviouslySeenTopFeedEntitiesCacheAvailable(int $limit = 100): array
    {
        $cacheKey = $this->getCacheKey();

        $cursor = null;

        $data = $this->redisClient->sScan($cacheKey, $cursor, null, $limit);
        return !$data ? [] : $data;
    }
}
