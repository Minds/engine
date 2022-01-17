<?php

namespace Minds\Core\Recommendations\Config;

use Minds\Core\Recommendations\Algorithms\WiderNetworkRecommendationsAlgorithm;
use Minds\Core\Recommendations\Locations\FeedSidebarLocation;
use Minds\Core\Recommendations\Locations\TestWiderNetworkLocation;

final class RecommendationsLocationsMappingConfig
{
    /**
     * @type string[]
     */
    public const MAPPING = [
        "feed-sidebar" => FeedSidebarLocation::class,
        "test-wider-network" => TestWiderNetworkLocation::class
    ];
}
