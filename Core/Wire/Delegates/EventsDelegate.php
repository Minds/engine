<?php

namespace Minds\Core\Wire\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Wire\Wire;

class EventsDelegate
{
    /** @var ActionEventsTopic */
    protected $actionEventsTopic;

    public function __construct(ActionEventsTopic $actionEventsTopic = null)
    {
        $this->actionEventsTopic = $actionEventsTopic ?? Di::_()->get('EventStreams\Topics\ActionEventsTopic');
    }

    /**
     * OnAdd, submit the ActionEvent
     * @param Wire $wire
     * @return void
     */
    public function onAdd(Wire $wire) : void
    {
        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_WIRE_SENT)
            ->setEntity($wire)
            ->setUser($wire->getSender());

        $this->actionEventsTopic->send($actionEvent);
    }
}
