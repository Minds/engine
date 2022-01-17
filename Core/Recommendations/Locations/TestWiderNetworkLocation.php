<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Algorithms\WiderNetworkRecommendationsAlgorithm;
use Minds\Entities\User;

class TestWiderNetworkLocation implements LocationInterface
{
    private ?User $user;

    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        return (new WiderNetworkRecommendationsAlgorithm())->setUser($this->user);
    }

    public function setUser(?User $user): LocationInterface
    {
        $this->user = $user;
        return $this;
    }
}
