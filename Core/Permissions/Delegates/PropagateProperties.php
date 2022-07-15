<?php

namespace Minds\Core\Permissions\Delegates;

use Minds\Core\Entities\Propagator\Properties;
use Minds\Entities\Activity;

/**
 * Class PropagateProperties
 * @package Minds\Core\Permissions\Delegates
 */
class PropagateProperties extends Properties
{
    /**
     * Propagate Entity properties to activity
     * @param $from
     * @param Activity $to
     * @return Activity
     */
    public function toActivity($from, Activity $to): Activity
    {
        if ($this->valueHasChanged($from->getAllowComments(), $to->getAllowComments())) {
            $to->setAllowComments($from->getAllowComments());
        }

        if ($this->valueHasChanged($from->getAccessId(), $to->getAccessId())) {
            $to->setAccessId($from->getAccessId());
        }

        return $to;
    }

    /**
     * Propagate activity properties to entity
     * @param Activity $from
     * @param $to
     * @return mixed
     */
    public function fromActivity(Activity $from, $to)
    {
        if ($this->valueHasChanged($from->getAllowComments(), $to->getAllowComments())) {
            $to->setAllowComments($from->getAllowComments());
        }

        if ($this->valueHasChanged($from->getAccessId(), $to->getAccessId())) {
            $to->setAccessId($from->getAccessId());
        }

        return $to;
    }
}
