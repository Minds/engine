<?php

namespace Minds\Core\Recommendations\Config;

use Minds\Core\Recommendations\Algorithms\WiderNetworkRecommendationsAlgorithm;
use Minds\Core\Recommendations\Locations\FeedSidebarLocation;
use Minds\Core\Recommendations\Locations\WiderNetworkLocation;

final class RecommendationsLocationsMappingConfig
{
    /**
     * Mapping between UI recommendations locations and business logic implementation
     * @type string[]
     */
    public const MAPPING = [
        "feed-sidebar" => FeedSidebarLocation::class,
        "wider-network" => WiderNetworkLocation::class
    ];
}
