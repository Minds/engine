<?php
/**
 * Trigger events
 */
namespace Minds\Core\Subscriptions\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;
use Minds\Core\Subscriptions\Subscription;

class EventsDelegate
{
    /** @var EventsDispatcher */
    private $eventsDispatcher;

    /** @var ActionEventsTopic */
    private $actionEventsTopic;

    public function __construct($eventsDispatcher = null, ActionEventsTopic $actionEventsTopic = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->actionEventsTopic = $actionEventsTopic ?? new ActionEventsTopic();
    }

    /**
     * Trigger an event
     * @param Subscription $subscrition
     * @return void
     */
    public function trigger(Subscription $subscription)
    {
        $this->eventsDispatcher->trigger($subscription->isActive() ? 'subscribe' : 'unsubscribe', 'all', [
            'user_guid' => $subscription->getSubscriberGuid(),
            'to_guid' => $subscription->getPublisherGuid(),
            'subscription' => $subscription,
        ]);

        $actionEvent = new ActionEvent();
        $actionEvent->setAction($subscription->isActive() ? ActionEvent::ACTION_SUBSCRIBE : ActionEvent::ACTION_UNSUBSCRIBE)
            ->setUser($subscription->getSubscriber())
            ->setEntity($subscription->getPublisher());

        $this->actionEventsTopic->send($actionEvent);
    }
}
