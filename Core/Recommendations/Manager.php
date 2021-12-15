<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Config\RecommendationsLocationsMappingConfig;
use Minds\Core\Recommendations\Locations\LocationInterface;
use Minds\Entities\User;

class Manager implements ManagerInterface
{
    private LocationInterface $location;

    private function createLocation($userGuid, string $location): void
    {
        $locationClass = RecommendationsLocationsMappingConfig::MAPPING[$location];
        $this->location = new $locationClass();
        $this->location->setUser($userGuid);
    }

    public function getRecommendations(?User $user, string $location): Response
    {
        $this->createLocation();
        $algorithm = $this->location->getLocationRecommendationsAlgorithm();

        return new Response([
            "algorithm" => $algorithm->getFriendlyName(),
            "entities" => $algorithm->getRecommendations()
        ]);
    }
}
