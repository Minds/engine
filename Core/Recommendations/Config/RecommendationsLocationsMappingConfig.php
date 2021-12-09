<?php

namespace Minds\Core\Recommendations\Config;

use Minds\Core\Recommendations\Locations\FeedSidebarLocation;

final class RecommendationsLocationsMappingConfig
{
    /**
     * @type string[]
     */
    public const MAPPING = [
        "feed-sidebar" => FeedSidebarLocation::class
    ];
}
