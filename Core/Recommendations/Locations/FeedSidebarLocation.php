<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;

/**
 * Deals with instantiating and returning the correct recommendations' algorithm to return for the feed-sidebar location
 */
class FeedSidebarLocation extends AbstractRecommendationsLocation
{
    public function __construct()
    {
    }

    /**
     * Creates an instance
     * @return RecommendationsAlgorithmInterface
     */
    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        return (new SuggestedChannelsRecommendationsAlgorithm())->setUser($this->user);
    }
}
