<?php

namespace Minds\Core\Boost\Network;

use Minds\Helpers;

class Metrics
{
    /**
     * Increments boost total views
     * @param Boost|Campaign $boost
     * @return int Updated boost total views
     */
    public function incrementTotalViews($boost): int
    {
        Helpers\Counters::increment((string) $boost->getGuid(), $this->getTotalKey(), 1);
        Helpers\Counters::increment(0, $this->getTotalKey(), 1);

        return Helpers\Counters::get((string) $boost->getGuid(), $this->getTotalKey(), false);
    }

    /**
     * Increment boost daily views
     * @param Boost|Campaign $boost
     * @return int Updated boost daily views
     */
    public function incrementDailyViews($boost): int
    {
        Helpers\Counters::increment((string) $boost->getGuid(), $this->getDailyKey(), 1);
        Helpers\Counters::increment(0, $this->getDailyKey(), 1);

        return Helpers\Counters::get((string) $boost->getGuid(), $this->getDailyKey(), false);
    }

    public function getTotalKey(): string
    {
        return 'boost_impressions';
    }

    public function getDailyKey(): string
    {
        return 'boost_impressions_' . date('dmy');
    }
}
