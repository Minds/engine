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

        return $this->getTotalViews($boost);
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

        return $this->getDailyViews($boost);
    }

    /**
     * Get the boost total views value
     * @param Boost|Campaign $boost
     * @return int Total boost views
     */
    public function getTotalViews($boost): int
    {
        return Helpers\Counters::get((string) $boost->getGuid(), $this->getTotalKey(), false);
    }

    /**
     * Get the boost daily views value
     * @param Boost|Campaign $boost
     * @return int Daily boost views
     */
    public function getDailyViews($boost): int
    {
        return Helpers\Counters::get((string) $boost->getGuid(), $this->getDailyKey(), false);
    }

    /**
     * Returns key for boost impressions metric
     * @return string
     */
    public function getTotalKey(): string
    {
        return 'boost_impressions';
    }

    /**
     * Returns key for boost daily impressions metric
     * @return string
     */
    public function getDailyKey(): string
    {
        return 'boost_impressions_' . date('dmy');
    }
}
