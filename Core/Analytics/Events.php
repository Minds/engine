<?php

namespace Minds\Core\Analytics;

use Minds\Core;
use Minds\Core\Events\Dispatcher;

class Events
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    public function __construct(Dispatcher $eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?? Core\Di\Di::_()->get('EventsDispatcher');
    }

    public function register()
    {
        $this->eventsDispatcher->register('user_state_change', 'all', function (Core\Events\Event $event) {
            $userState = Core\Analytics\UserStates\UserState::fromArray($event->getParameters());
            (new Core\Analytics\Delegates\UpdateUserState($userState))->update();
        });
    }
}
