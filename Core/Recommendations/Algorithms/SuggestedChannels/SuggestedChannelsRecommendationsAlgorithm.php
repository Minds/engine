<?php

namespace Minds\Core\Recommendations\Algorithms\SuggestedChannels;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Recommendations\Algorithms\AbstractRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Subscriptions\Relational\Repository;
use Minds\Core\Suggestions\Suggestion;
use Psr\SimpleCache\CacheInterface;

/**
 * Recommendations algorithm to retrieve suggested channels for the logged-in user
 */
class SuggestedChannelsRecommendationsAlgorithm extends AbstractRecommendationsAlgorithm
{
    public function __construct(
        private ?AlgorithmOptions $options = null,
        private ?Repository $repository = null,
        private ?CacheInterface $cache = null
    ) {
        $this->options = $this->options ?? new AlgorithmOptions();
        $this->repository = $this->repository ?? Di::_()->get("Subscriptions\Relational\Repository");
        $this->cache ??= Di::_()->get('Cache\PsrWrapper');
    }

    /**
     * Returns the list of recommendations based on the current recommendation's algorithm
     * @param array|null $options
     * @return Response
     */
    public function getRecommendations(?array $options = []): Response
    {
        $cacheKey = "suggested-channels-offset::" . $this->user->getGuid();
        $cachedOffset = $this->cache->get($cacheKey, 0);
        
        $limit = (int) $options['limit'] ?? 3;

        $users = array_map(function ($user) {
            $suggestion = new Suggestion();
            $suggestion->setEntity($user)
                ->setEntityType('user');
            return $suggestion;
        }, iterator_to_array($this->repository->getSubscriptionsOfSubscriptions(
            userGuid: $this->user->getGuid(),
            limit: (int) $options['limit'],
            offset: (int) ($options['offset'] ?? $cachedOffset),
        )));

        $nextOffset = count($users) === $limit ? $cachedOffset + $limit : 0;
        $this->cache->set($cacheKey, $nextOffset, 86400);

        return new Response($users);
    }
}
