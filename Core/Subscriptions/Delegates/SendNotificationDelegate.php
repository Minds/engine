<?php
/**
 * Send a notification
 */
namespace Minds\Core\Subscriptions\Delegates;

use Minds\Core\Di\Di;

class SendNotificationDelegate 
{

    /** @var EventsDispatcher $eventsDispatcher */
    private $eventsDispatcher;

    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * Send a notifications 
     * @param Subscription $subscrition
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

    }

    public function onMaxSubscriptions($subscription)
    {
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
