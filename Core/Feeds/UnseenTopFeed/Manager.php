<?php

namespace Minds\Core\Feeds\UnseenTopFeed;

use Exception;
use Minds\Common\PseudonymousIdentifier;
use Minds\Common\Repository\Response;
use Minds\Core;
use Minds\Core\Data\cache\Redis;
use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Manager as ElasticSearchManager;

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
     * @param int $limit
     * @return Response
     * @throws Exception
     */
    public function getUnseenTopEntities(
        int $limit
    ): Response {
        /** @var User $user */
        $user = Core\Session::getLoggedinUser();

        $queryOptions = [
            'cache_key' => $user->guid,
            'subscriptions' => $user->guid,
            'access_id' => 2,
            'limit' => $limit,
            'type' => 'activity',
            'algorithm' => 'top',
            'period' => 'all', // legacy option
            'single_owner_threshold' => 0,
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
        $this->redisClient?->set(
            $this->getCacheKey(),
            array_merge(
                $this->getUserPreviouslySeenTopFeedEntitiesCacheAvailable(),
                $entityGuids
            )
        );
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
}
