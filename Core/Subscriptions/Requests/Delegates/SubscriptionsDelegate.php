<?php
namespace Minds\Core\Subscriptions\Requests\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Subscriptions\Manager as SubscriptionsManager;
use Minds\Core\Subscriptions\Requests\SubscriptionRequest;

class SubscriptionsDelegate
{
    /** @var SubscriptionsManager */
    private $subscriptionsManager;

    /** @var EntitiesBuilder */
    private $entitiesBuilder;

    public function __construct($subscriptionsManager = null, $entitiesBuilder = null)
    {
        $this->subscriptionsManager = $subscriptionsManager ?? Di::_()->get('Subscriptions\Manager');
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
    }

    /**
     * Called when subscription request is accepted
     * @param SubscriptionRequest $subscriptionRequest
     * @return void
     */
    public function onAccept(SubscriptionRequest $subscriptionRequest): void
    {
        $subscriber = $this->entitiesBuilder->single($subscriptionRequest->getSubscriberGuid());
        $publisher = $this->entitiesBuilder->single($subscriptionRequest->getPublisherGuid());

        $this->subscriptionsManager->setSubscriber($subscriber);
        $this->subscriptionsManager->subscribe($publisher, true);
    }
}
