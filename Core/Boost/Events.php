<?php

namespace Minds\Core\Boost;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments;
use Minds\Core\Email\V2\Campaigns\Recurring\BoostComplete\BoostComplete;
use Minds\Core\Events\Event;

/**
 * Minds Payments Events.
 */
class Events
{
    public function register()
    {
        // @deprecated
        Dispatcher::register('boost:completed', 'boost', function ($event) {
            $campaign = new BoostComplete();
            $params = $event->getParameters();
            $boost = $params['boost'];
            $campaign->setUser($boost->getOwner())
                ->setBoost($boost->export());
            $campaign->send();

            return $event->setResponse(true);
        });
    }
}
