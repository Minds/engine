<?php

namespace Minds\Core\Boost\Network;

use Minds\Helpers;

class Metrics
{
    /**
     * Increments impressions to a given boost
     * @param Boost $boost
     * @return int updated boost impressions count
     */
    public function incrementViews($boost): int
    {
        Helpers\Counters::increment((string) $boost->getGuid(), "boost_impressions", 1);
        Helpers\Counters::increment(0, "boost_impressions", 1);

        return Helpers\Counters::get((string) $boost->getGuid(), "boost_impressions", false);
    }
}
