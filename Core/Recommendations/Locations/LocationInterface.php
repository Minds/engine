<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Entities\User;

interface LocationInterface
{
    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface;

    public function setUser(?User $user): self;
}
