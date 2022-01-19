<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Algorithms\SuggestedChannelsRecommendationsAlgorithm;

/**
 * Deals with instantiating and returning the correct recommendations' algorithm to return for the feed-sidebar location
 */
class FeedSidebarLocation extends AbstractRecommendationsLocation
{
    /**
     * Creates an instance
     * @return RecommendationsAlgorithmInterface
     */
    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        return (new SuggestedChannelsRecommendationsAlgorithm())->setUser($this->user);
    }
}
