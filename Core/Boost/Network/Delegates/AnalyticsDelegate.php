<?php
namespace Minds\Core\Boost\Network\Delegates;

use Minds\Core;
use Minds\Core\Boost\Network\Boost;

class AnalyticsDelegate implements BoostDelegateInterface
{
    /**
     * Called when a boost is added
     * @param Boost $boost
     * @return void
     */
    public function onAdd(Boost $boost): void
    {
        $event = new Core\Analytics\Metrics\Event();
        $event->setType('action')
            ->setAction('boost')
            ->setProduct('boost')
            ->setUserGuid(Core\Session::getLoggedInUserGuid())
            ->setEntityGuid((string) $boost->getGuid()) // Note its the boost guid and not the entity being boosted
            ->setEntityType('boost')
            ->setEntityOwnerGuid($boost->getOwnerGuid())
            ->setBoostEntityGuid($boost->getEntityGuid())
            ->setBoostType($boost->getType())
            ->push();
    }
}
