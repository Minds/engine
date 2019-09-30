<?php

namespace Minds\Core\Feeds\Delegates;

use Minds\Core\Blogs\Blog;
use Minds\Core\Entities\Propagator\Properties;
use Minds\Entities\Activity;
use Minds\Entities\Entity;

/**
 * Class PropagateProperties
 * @package Minds\Core\Feeds\Delegates
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
        if ($this->valueHasChanged((int)$from->getModeratorGuid(), (int)$to->getModeratorGuid())) {
            $to->setModeratorGuid((int)$from->getModeratorGuid());
        }

        if ($this->valueHasChanged((int)$from->getTimeModerated(), (int)$to->getTimeModerated())) {
            $to->setTimeModerated((int)$from->getTimeModerated());
        }

        return $to;
    }

    /**
     * Propagate activity properties to entity
     * @param Activity $from
     * @param Entity|Blog $to
     * @return mixed
     */
    public function fromActivity(Activity $from, $to)
    {
        if ($this->valueHasChanged((int)$from->getModeratorGuid(), (int)$to->getModeratorGuid())) {
            $to->setModeratorGuid((int)$from->getModeratorGuid());
        }

        if ($this->valueHasChanged((int)$from->getTimeModerated(), (int)$to->getTimeModerated())) {
            $to->setTimeModerated((int)$from->getTimeModerated());
        }

        $to = $this->propagateAttachmentPaywallProperties($from, $to);
        return $to;
    }

    /**
     * @param Activity $from
     * @param Entity $to
     * @return mixed
     */
    private function propagateAttachmentPaywallProperties(Activity $from, $to)
    {
        if ($to->owner_guid == $from->owner_guid) {
            $newAccessId = $from->isPaywall() ? 0 : 2;
            if ($this->valueHasChanged($to->access_id, $from->access_id)) {
                $to->access_id = $newAccessId;
            }

            $newHidden = $from->isPayWall();
            if ($to->getSubtype() === 'blog') {
                /** @var $to Blog */
                if ($this->valueHasChanged($to->getHidden(), $newHidden)) {
                    $to->setHidden($newHidden);
                }
            } else {
                if ($this->valueHasChanged($to->hidden, $newHidden)) {
                    $to->hidden = $newHidden;
                }
            }

            if (method_exists($to, 'setFlag')) {
                if ($this->valueHasChanged($to->getFlag('paywall'), (bool)$from->isPaywall())) {
                    $to->setFlag('paywall', (bool)$from->isPaywall());
                }
            }

            if (method_exists($to, 'setWireThreshold')) {
                if ($this->valueHasChanged($to->getWireThreshold(), $from->getWireThreshold())) {
                    $to->setWireThreshold($from->getWireThreshold() ?: false);
                }
            }
        }

        return $to;
    }
}
