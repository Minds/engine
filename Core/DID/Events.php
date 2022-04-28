<?php
/**
 * DID events.
 */

namespace Minds\Core\DID;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Entities\User;

class Events
{
    public function __construct(private ?Manager $manager = null)
    {
        // See below for why this is commented out
        // $this->manager ??= Di::_()->get('DID\Manager');
    }

    public function register()
    {
        Dispatcher::register('export:extender', 'all', function ($event) {
            $params = $event->getParameters();
            $export = $event->response() ?: [];
            $user = $params['entity'];

            if (!$user instanceof User) {
                return;
            }

            // We use DI here because of order of operations issues with the constructor
            // The module is registering events and calling the contructor too soon
            if (!$this->manager) {
                $this->manager =  Di::_()->get('DID\Manager');
            }

            $export['did'] = $this->manager->getId($user);
            $event->setResponse($export);
        });
    }
}
