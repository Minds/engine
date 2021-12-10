<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Exception;
use Minds\Common\PseudonymousIdentifier;
use Minds\Common\Repository\Response;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;
use Minds\Core\Feeds\FeedSyncEntity;

class Manager implements ManagerInterface
{
    private const CACHE_KEY_PREFIX = "seen-entities";

    public function __construct(
        private ?Redis $redisClient = null,
        private ?ElasticSearchManager $elasticSearchManager = null
    ) {
        $this->redisClient = $this->redisClient ?? Di::_()->get("Cache\Redis");
        $this->elasticSearchManager = $this->elasticSearchManager ?? Di::_()->get("Feeds\Elastic\Manager");
    }

    /**
     * @param int $totalEntitiesToRetrieve
     * @return Response
     * @throws Exception
     */
    public function getUnseenTopEntities(
        int $totalEntitiesToRetrieve
    ): Response {
        $queryOptions = [
            'limit' => $totalEntitiesToRetrieve,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => 'all' // legacy option
        ];

        $previouslySeenEntities = $this->getUserPreviouslySeenTopFeedEntitiesCacheAvailable();
        if (count($previouslySeenEntities) > 0) {
            $queryOptions['exclude'] = $previouslySeenEntities;
        }

        $response = $this->elasticSearchManager->getList($queryOptions);

        $entitiesGuids = $this->createArrayWithLatestEntitiesGuids($response);
        $this->updateUserPreviousSeenTopFeedEntitiesCache($entitiesGuids);

        return $response;
    }

    private function createUnseenTopFeedCacheKeyCookie(): UnseenTopFeedCacheKeyCookie
    {
        return (new UnseenTopFeedCacheKeyCookie())->createCookie();
    }

    private function getCacheKey(): string
    {
        return self::CACHE_KEY_PREFIX . ((new PseudonymousIdentifier())->getId() ?? $this->createUnseenTopFeedCacheKeyCookie()->getValue());
    }

    /**
     * @return string[]
     */
    private function getUserPreviouslySeenTopFeedEntitiesCacheAvailable(): array
    {
        $cacheKey = $this->getCacheKey();

        $data = $this->redisClient->get($cacheKey);
        return !$data ? [] : $data;
    }

    /**
     * @param Response $entities
     * @return string[]
     */
    private function createArrayWithLatestEntitiesGuids(Response $entities): array
    {
        $entitiesGuids = [];

        /**
         * @var FeedSyncEntity $entity
         */
        foreach ($entities as $entity) {
            $entitiesGuids[] = $entity->getGuid();
        }

        return $entitiesGuids;
    }

    /**
     * @param string[] $entitiesGuids
     * @return void
     */
    private function updateUserPreviousSeenTopFeedEntitiesCache(array $entitiesGuids): void
    {
        $this->redisClient?->set(
            $this->getCacheKey(),
            array_merge(
                $this->getUserPreviouslySeenTopFeedEntitiesCacheAvailable(),
                $entitiesGuids
            )
        );
    }
}
