<?php

namespace Minds\Core\Recommendations\Locations;

interface LocationInterface
{
    public function getLocationQuery(): string;

    public function getLocationRecommendations(): array;
}
