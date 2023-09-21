<?php
namespace Minds\Core\Recommendations\Algorithms\SuggestedGroups;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Recommendations\Algorithms\AbstractRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Groups\V2\Membership\Repository;
use Minds\Core\Suggestions\Suggestion;
use Psr\SimpleCache\CacheInterface;

/**
 * Recommendations algorithm to retrieve suggested groups for the logged-in user
 */
class SuggestedGroupsRecommendationsAlgorithm extends AbstractRecommendationsAlgorithm
{
    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?Repository $repository = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?CacheInterface $cache = null
    ) {
        $this->options = $this->options ?? new AlgorithmOptions();
        $this->repository = $this->repository ?? Di::_()->get(Repository::class);
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Returns the list of recommendations based on the current recommendation's algorithm
     * @param array|null $options
     * @return Response
     */
    public function getRecommendations(?array $options = []): Response
    {
        $cacheKey = $this->getCacheKey();
        $cacheKeyOffset = $this->getOffsetCacheKey();
        $cachedOffset = (int) $this->cache->get($cacheKeyOffset, 0);
        
        $limit = (int) $options['limit'] ?? 3;

        if ($cachedGroupGuids = $this->cache->get($cacheKey)) {
            $groupsGuids = unserialize($cachedGroupGuids);
        } else {
            /**
             * Fetch 100 items, and then slice the array after (better performance when paired with caching)
             */
            $groupsGuids = iterator_to_array($this->repository->getGroupsOfMutualMember(
                userGuid: $this->user->getGuid(),
                limit: 100,
                offset: 0,
            ));
            $this->cache->set($cacheKey, serialize($groupsGuids));
        }

        $groups = array_map(function ($groupGuid) {
            $group = $this->entitiesBuilder->single($groupGuid);
            $suggestion = new Suggestion();
            $suggestion->setEntity($group)
                ->setEntityType('group');
            return $suggestion;
        }, array_slice($groupsGuids, (int) ($options['offset'] ?? $cachedOffset), (int) $options['limit']));

        $nextOffset = count($groups) === $limit ? $cachedOffset + $limit : 0;
        $this->cache->set($cacheKeyOffset, $nextOffset, 86400);

        return new Response($groups);
    }

    /**
     * Purges the recs cache
     */
    public function purgeCache(): void
    {
        $this->cache->delete($this->getCacheKey());
        $this->cache->delete($this->getOffsetCacheKey());
    }

    private function getCacheKey(): string
    {
        return "suggested-groups-guids::" . $this->user->getGuid();
    }

    private function getOffsetCacheKey(): string
    {
        return "suggested-groups-offset::" . $this->user->getGuid();
    }
}
