<?php

namespace Minds\Core\Entities\Delegates;

use Minds\Core\Entities\Propagator\Properties;
use Minds\Entities\Activity;

/**
 * Class PropagateProperties
 * @package Minds\Core\Entities\Delegates
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
        if ($this->valueHasChanged($from->getNsfw(), $to->getNsfw())) {
            $to->setNsfw($from->getNsfw());
        }

        if ($this->valueHasChanged($from->getNsfwLock(), $to->getNsfwLock())) {
            $to->setNsfwLock($from->getNsfwLock());
        }

        if ($this->valueHasChanged($from->getTags(), $to->getTags())) {
            $to->setTags($from->getTags() ?? []);
        }

        if ($this->valueHasChanged($from->getLicense(), $to->getLicense())) {
            $to->setLicense($from->getLicense() ?? '');
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
        if ($this->valueHasChanged($from->getNsfw(), $to->getNsfw())) {
            $to->setNsfw($from->getNsfw());
        }

        if ($this->valueHasChanged($from->getNsfwLock(), $to->getNsfwLock())) {
            $to->setNsfwLock($from->getNsfwLock());
        }

        if ($this->valueHasChanged($from->getTags(), $to->getTags())) {
            $to->setTags($from->getTags() ?? []);
        }

        if ($this->valueHasChanged($from->getLicense(), $to->getLicense())) {
            $to->setLicense($from->getLicense() ?? '');
        }

        return $to;
    }
}
