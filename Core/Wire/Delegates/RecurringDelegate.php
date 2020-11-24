<?php

namespace Minds\Core\Wire\Delegates;

use Minds\Core\Di\Di;
use Minds\Core\Wire\Wire;
use Minds\Core\Payments\Subscriptions\Subscription;

class RecurringDelegate
{
    /** @var $subscriptionsManager */
    private $subscriptionsManager;

    public function __construct($subscriptionsManager = null)
    {
        $this->subscriptionsManager = $subscriptionsManager ?? Di::_()->get('Payments\Subscriptions\Manager');
    }

    /**
     * OnAdd, make subscription
     * @param Wire $wire
     * @return void
     */
    public function onAdd(Wire $wire): void
    {
        $urn = "urn:subscription:" . implode('-', [
            $wire->getAddress(), //offchain or onchain wallet or usd
            $wire->getSender()->getGuid(),
            $wire->getReceiver()->getGuid(),
        ]);

        $subscription = (new Subscription())
            ->setId($urn)
            ->setPlanId('wire')
            ->setPaymentMethod($wire->getMethod())
            ->setAmount($wire->getAmount())
            ->setUser($wire->getSender())
            ->setEntity($wire->getReceiver())
            ->setInterval($wire->getRecurringInterval())
            ->setTrialDays($wire->getTrialDays());

        $this->subscriptionsManager->setSubscription($subscription);
        $this->subscriptionsManager->create();
    }
}
