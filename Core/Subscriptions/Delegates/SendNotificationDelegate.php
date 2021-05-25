<?php
/**
 * Send a notification
 */
namespace Minds\Core\Subscriptions\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EventStreams\ActionEvent;
use Minds\Core\EventStreams\Topics\ActionEventsTopic;

class SendNotificationDelegate
{
    /** @var EventsDispatcher $eventsDispatcher */
    private $eventsDispatcher;

    /** @var EntitiesBuilder $entitiesBuilder */
    protected $entitiesBuilder;

    public function __construct($eventsDispatcher = null, $entitiesBuilder = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
        $this->entitiesBuilder = $entitiesBuilder ?: Di::_()->get('EntitiesBuilder');
    }

    /**
     * Send a notifications
     * @param Subscription $subscription
     * @return void
     */
    public function send($subscription)
    {
        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => [ $subscription->getPublisherGuid() ],
            'entity' => $subscription->getSubscriberGuid(),
            'notification_view' => 'friends',
            'from' => $subscription->getSubscriberGuid(),
            'params' => [],
        ]);

        $subscriber = $this->entitiesBuilder->single($subscription->getSubscriberGuid());
        $publisher = $this->entitiesBuilder->single($subscription->getPublisherGuid());

        $actionEvent = new ActionEvent();
        $actionEvent
            ->setAction(ActionEvent::ACTION_SUBSCRIBE)
            ->setEntity($publisher)
            ->setUser($subscriber);

        $actionEventTopic = new ActionEventsTopic();
        $actionEventTopic->send($actionEvent);
    }

    public function onMaxSubscriptions($subscription)
    {
        // TODO OJM make this into a toast notification
        $message = "You are unable to subscribe to new channels as you have over 5000 subscriptions.";
        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => [ $subscription->getSubscriberGuid() ],
            'entity' => $subscription->getPublisherGuid(),
            'notification_view' => 'custom_message',
            'from' => 100000000000000519,
            'message' => $message,
            'params' => [ 'message' => $message],
        ]);
    }
}
