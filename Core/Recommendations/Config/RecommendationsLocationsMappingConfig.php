<?php

namespace Minds\Core\Recommendations\Config;

use Minds\Core\Recommendations\Locations\ChannelLocation;
use Minds\Core\Recommendations\Locations\FeedSidebarLocation;
use Minds\Core\Recommendations\Locations\WiderNetworkLocation;
use Minds\Core\Recommendations\Locations\DiscoveryFeedLocation;
use Minds\Core\Recommendations\Locations\NewsfeedLocation;

final class RecommendationsLocationsMappingConfig
{
    /**
     * Mapping between UI recommendations locations and business logic implementation
     * @type string[]
     */
    public const MAPPING = [
        "feed-sidebar" => FeedSidebarLocation::class,
        "wider-network" => WiderNetworkLocation::class,
        "newsfeed" => NewsfeedLocation::class,
        "discovery-feed" => DiscoveryFeedLocation::class,
        "channel" => ChannelLocation::class,
    ];
}
