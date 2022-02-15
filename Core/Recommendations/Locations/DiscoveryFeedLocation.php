<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Algorithms\SuggestedChannels\SuggestedChannelsRecommendationsAlgorithm;

class DiscoveryFeedLocation extends AbstractRecommendationsLocation
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
