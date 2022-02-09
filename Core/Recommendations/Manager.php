<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Algorithms\AlgorithmOptions;
use Minds\Core\Recommendations\Algorithms\WiderNetwork\WiderNetworkRecommendationsAlgorithm;
use Minds\Core\Recommendations\Config\RecommendationsLocationsMappingConfig;
use Minds\Core\Recommendations\Locations\LocationInterface;
use Minds\Entities\User;
use Generator;

/**
 * Recommendations manager
 */
class Manager implements ManagerInterface
{
    private LocationInterface $location;

    /**
     * Generates an instance of the location handler for the recommendations based of the details provided to the Http request
     * @param User|null $user
     * @param string $location
     * @return void
     */
    private function createLocation(?User $user, string $location): void
    {
        $locationClass = RecommendationsLocationsMappingConfig::MAPPING[$location];
        $this->location = new $locationClass();
        $this->location->setUser($user);
    }

    /**
     * Retrieves the recommendations based on the location provided
     * @param User|null $user
     * @param string $location
     * @return Response
     */
    public function getRecommendations(?User $user, string $location): Response
    {
        $this->createLocation($user, $location);
        $algorithm = $this->location->getLocationRecommendationsAlgorithm();

        return new Response([
            "algorithm" => $algorithm->getFriendlyName(),
            "entities" => $algorithm->getRecommendations()->toArray()
        ]);
    }

    /**
     * @param WiderNetworkRecommendationsAlgorithm|null $algorithm
     * @param AlgorithmOptions|null $options
     * @return Generator|Suggestion[]
     */
    public function getDiscoveryForYouFeedRecommendations(
        ?WiderNetworkRecommendationsAlgorithm $algorithm = null,
        ?AlgorithmOptions $options = null
    ): Generator {
        $algorithm ??= new WiderNetworkRecommendationsAlgorithm();

        return $algorithm
            ->setOptions($options)
            ->getDiscoveryForYouFeedRecommendations();
    }
}
