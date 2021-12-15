<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Entities\User;

class TestWiderNetworkLocation implements LocationInterface
{
    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        // TODO: Implement getLocationRecommendationsAlgorithm() method.
    }

    public function setUser(?User $user): LocationInterface
    {
        // TODO: Implement setUser() method.
    }
}
