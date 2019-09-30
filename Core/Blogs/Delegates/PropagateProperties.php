<?php

namespace Minds\Core\Blogs\Delegates;

use Minds\Core\Entities\Propagator\Properties;
use Minds\Entities\Activity;

/**
 * Class PropagateProperties
 * @package Minds\Core\Blogs\Delegates
 */
class PropagateProperties extends Properties
{
    protected $actsOnSubtype = ['blog'];

    /**
     * Propagate Entity properties to activity
     * @param $from
     * @param Activity $to
     * @return Activity
     */
    public function toActivity($from, Activity $to): Activity
    {
        if ($this->valueHasChanged($from->getTitle(), $to->get('title'))) {
            $to->set('title', $from->getTitle());
        }

        $blurb = strip_tags($from->getBody());
        if ($this->valueHasChanged($blurb, $to->get('blurb'))) {
            $to->set('blurb', $blurb);
        }

        if ($this->valueHasChanged($from->getUrl(), $to->getURL())) {
            $to->setURL($from->getUrl());
        }

        if ($this->valueHasChanged($from->getIconUrl(), $to->get('thumbnail_src'))) {
            $to->set('thumbnail_src', $from->getIconUrl());
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
        return $to;
    }
}
