<?php

namespace Minds\Core\Recommendations;

use Minds\Common\Repository\Response;
use Minds\Core\Recommendations\Config\RecommendationsLocationsMappingConfig;
use Minds\Core\Recommendations\Locations\LocationInterface;

class Manager implements ManagerInterface
{
    private LocationInterface $location;

    private function createLocation(string $location): void
    {
        $locationClass = RecommendationsLocationsMappingConfig::MAPPING[$location];
        $this->location = new $locationClass();
    }

    public function getRecommendations(string $location): Response
    {
        $this->createLocation($location);

        return new Response([
            "query" => $this->location->getLocationQuery(),
            "entities" => $this->location->getLocationRecommendations()
        ]);
    }
}
