<?php

namespace Minds\Core\Boost\Network;

use Minds\Core\Boost\Repository;
use Minds\Core\Data;
use Minds\Core\Di\Di;
use Minds\Entities\Boost\Network;
use Minds\Helpers;

class Metrics
{
    protected $type;

    /**
     * @param string $type
     * @return $this
     */
    public function setType($type)
    {
        $this->type = strtolower($type);
        return $this;
    }

    /**
     * Increments impressions to a given boost
     * @param Network $boost
     * @return int updated boost impressions count
     */

    public function incrementViews($boost)
    {
        //increment impression counter
        Helpers\Counters::increment((string) $boost->getGuid(), "boost_impressions", 1);
        //get the current impressions count for this boost
        Helpers\Counters::increment(0, "boost_impressions", 1);

        $count = Helpers\Counters::get((string) $boost->getGuid(), "boost_impressions", false);

        if ($boost->getMongoId()) {
            $count += Helpers\Counters::get((string) $boost->getMongoId(), "boost_impressions", false);
        }

        return $count;
    }

    public function getBacklogCount($userGuid = null)
    {
        return -1;
    }

    public function getPriorityBacklogCount()
    {
        return -1;
    }

    public function getBacklogImpressionsSum()
    {
        return -1;
    }

    public function getAvgApprovalTime()
    {
        return -1;
    }
}
