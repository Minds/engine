<?php
namespace Minds\Core\Subscriptions\Requests\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Subscriptions\Requests\SubscriptionRequest;

class NotificationsDelegate
{
    /** @var EventsDispatcher */
    private $eventsDispatcher;

    public function __construct($eventsDispatcher = null)
    {
        $this->eventsDispatcher = $eventsDispatcher ?: Di::_()->get('EventsDispatcher');
    }

    /**
     * Called when subscription request is made
     * @param SubscriptionRequest $subscriptionRequest
     * @return void
     */
    public function onAdd(SubscriptionRequest $subscriptionRequest): void
    {
        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => [ $subscriptionRequest->getPublisherGuid() ],
            'entity' => $subscriptionRequest->getSubscriberGuid(),
            'notification_view' => 'subscription_request_received',
            'from' => $subscriptionRequest->getSubscriberGuid(),
            'params' => [],
        ]);
    }

    /**
     * Called when subscription request is accepted
     * @param SubscriptionRequest $subscriptionRequest
     * @return void
     */
    public function onAccept(SubscriptionRequest $subscriptionRequest): void
    {
        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => [ $subscriptionRequest->getSubscriberGuid() ],
            'entity' => $subscriptionRequest->getPublisherGuid(),
            'notification_view' => 'subscription_request_accepted',
            'from' => $subscriptionRequest->getPublisherGuid(),
            'params' => [],
        ]);
    }

    /**
     * Called when subscription request is declined
     * @param SubscriptionRequest $subscriptionRequest
     * @return void
     */
    public function onDecline(SubscriptionRequest $subscriptionRequest): void
    {
        $this->eventsDispatcher->trigger('notification', 'all', [
            'to' => [ $subscriptionRequest->getSubscriberGuid() ],
            'entity' => $subscriptionRequest->getPublisherGuid(),
            'notification_view' => 'subscription_request_declined',
            'from' => $subscriptionRequest->getPublisherGuid(),
            'params' => [],
        ]);
    }
}
