<?php

namespace Minds\Core\Recommendations\Locations;

use Minds\Core\Di\Di;
use Minds\Core\Feeds\Elastic\Entities;
use Minds\Core\Suggestions\Manager;

class FeedSidebarLocation implements LocationInterface
{
    private string $locationQuery = "suggested-channels";

    public function __construct(
        private ?Manager $suggestionsManager = null
    ) {
        $this->suggestionsManager = $this->suggestionsManager ?? Di::_()->get("Suggestions\Manager");
    }

    public function getLocationQuery(): string
    {
        return $this->locationQuery;
    }

    public function getLocationRecommendations(): array
    {
        return $this->suggestionsManager?->getList()->toArray();
    }
}
