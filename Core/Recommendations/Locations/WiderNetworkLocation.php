<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Algorithms\WiderNetwork\WiderNetworkRecommendationsAlgorithm;

class WiderNetworkLocation extends AbstractRecommendationsLocation
{
    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        return (new WiderNetworkRecommendationsAlgorithm())->setUser($this->user);
    }
}
