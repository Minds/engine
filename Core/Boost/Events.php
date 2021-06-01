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
        // Urn resolve
        Dispatcher::register('urn:resolve', 'all', function (Event $event) {
            $urn = $event->getParameters()['urn'];

            if ($urn->getNid() !== 'peer-boost') {
                return;
            }
            
            /** @var Repository */
            $repository = Di::_()->get('Boost\Repository');
            $event->setResponse($repository->getEntity('peer', $urn->getNss()));
        });

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
