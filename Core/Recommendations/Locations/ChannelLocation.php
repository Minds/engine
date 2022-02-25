<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Recommendations\Algorithms\FriendsOfFriend\FriendsOfFriendRecommendationsAlgorithm;
use Minds\Core\Recommendations\Algorithms\RecommendationsAlgorithmInterface;

/**
 * Returns the recommendations' algorithm to be used for the channel location
 */
class ChannelLocation extends AbstractRecommendationsLocation
{
    /**
     * Creates an instance
     * @return RecommendationsAlgorithmInterface
     */
    public function getLocationRecommendationsAlgorithm(): RecommendationsAlgorithmInterface
    {
        return (new FriendsOfFriendRecommendationsAlgorithm())->setUser($this->user);
    }
}
