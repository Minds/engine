<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;
use Minds\Core\Recommendations\Algorithms\SuggestedChannelsRecommendationsAlgorithm;
use Minds\Entities\User;

class FeedSidebarLocation implements LocationInterface
{
    private ?User $user;

    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        return (new SuggestedChannelsRecommendationsAlgorithm())->setUser($this->user);
    }

    public function setUser(?User $user): LocationInterface
    {
        $this->user = $user;
        return $this;
    }
}
