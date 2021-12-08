<?php

/**
 * Entities events
 */

namespace Minds\Core\Entities;

use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Events\Event;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Sessions\ActiveSession;
use Minds\Entities\User;

class Events
{
    /** @var Dispatcher */
    protected $eventsDispatcher;

    /** @var ActionEventsTopic */
    protected $actionEventTopic;

    /** @var ActiveSession */
    protected $activeSession;

    public function __construct($eventsDispatcher = null, ActionEventsTopic $actionEventTopic = null, ActiveSession $activeSession = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->actionEventTopic = $actionEventTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
        $this->activeSession = $activeSession ?? Di::_()->get('Sessions\ActiveSession');
    }

    /**
     * Register listener for create events for all types of entities
     * @return void
     */
    public function register(): void
    {
        $this->eventsDispatcher->register('create', 'all', function ($event, $namespace, $entity) {
            $user = $this->activeSession->getUser();

            if (!$user) {
                return;
            }

            if (!$user && $entity instanceof User) {
                $user = $entity;
            }

            $actionEvent = new ActionEvent();
            $actionEvent
            ->setAction(ActionEvent::ACTION_CREATE)
            ->setEntity($entity)
            ->setUser($user);

            // $this->actionEventTopic->send($actionEvent);
        });
    }
}
