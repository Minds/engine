<?php

namespace Minds\Core\Recommendations;

use Generator;
use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Recommendations\Algorithms\WiderNetwork\WiderNetworkRecommendationsAlgorithm;
use Minds\Entities\User;

interface ManagerInterface
{
    /**
     * Retrieves the recommendations based on the location provided
     * @param User|null $user
     * @param string $location
     * @return Response
     */
    public function getRecommendations(?User $user, string $location): Response;

    /**
     * @param WiderNetworkRecommendationsAlgorithm|null $algorithm
     * @param AlgorithmOptions|null $options
     * @return Generator
     */
    public function getDiscoveryForYouFeedRecommendations(
        ?WiderNetworkRecommendationsAlgorithm $algorithm,
        ?AlgorithmOptions $options
    ): Generator;
}
