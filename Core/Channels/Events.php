<?php
/**
 * Minds Channels Events Listeners
 *
 * @author Mark
 */

namespace Minds\Core\Channels;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;

class Events
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /**
     * Events constructor.
     * @param Dispatcher $eventsDispatcher
     */
    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    public function register()
    {
        // User entity deletion
        $this->eventsDispatcher->register('entity:delete', 'user', function (Event $event) {
            $user = $event->getParameters()['entity'];
            $manager = Di::_()->get('Channels\Manager');
            $event->setResponse($manager->setUser($user)->delete());
        });
    }
}
