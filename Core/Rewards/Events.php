<?php
namespace Minds\Core\Rewards;

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
            /** @var Withdraw\Manager */
            $manager = Di::_()->get('Rewards\Withdraw\Manager');
            $event->setResponse($manager->getByUrn($urn));
        });
    }
}
