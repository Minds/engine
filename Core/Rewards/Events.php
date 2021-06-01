<?php
namespace Minds\Core\Rewards;

use Minds\Common\Urn;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\Dispatcher;

class Events
{
    public function register()
    {
        // Urn resolve
        Dispatcher::register('urn:resolve', 'all', function (Event $event) {
            $urn = $event->getParameters()['urn'];
        
            if ($urn->getNid() !== 'withdraw-request') {
                return;
            }

            /** @var Withdraw\Manager */
            $manager = Di::_()->get('Rewards\Withdraw\Manager');
            $event->setResponse($manager->getByUrn((string) $urn));
        });
    }
}
