<?php
/**
 * Subscription
 * @author edgebal
 */

namespace Minds\Core\Pro\Domain;

use Minds\Core\Analytics\Metrics\Event;
use Minds\Core\Di\Di;
use Minds\Core\Subscriptions\Manager as SubscriptionsManager;
use Minds\Entities\User;

class Subscription
{
    /** @var SubscriptionsManager */
    protected $subscriptionsManager;

    /** @var User */
    protected $user;

    /** @var User */
    protected $subscriber;

    /**
     * Subscription constructor.
     * @param SubscriptionsManager $subscriptionsManager
     */
    public function __construct(
        $subscriptionsManager = null
    )
    {
        $this->subscriptionsManager = $subscriptionsManager ?: Di::_()->get('Subscriptions\Manager');
    }

    /**
     * @param User $user
     * @return Subscription
     */
    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param User $subscriber
     * @return Subscription
     */
    public function setSubscriber(User $subscriber): self
    {
        $this->subscriber = $subscriber;
        return $this;
    }

    public function subscribe($trigger = true): void
    {
        $this->subscriptionsManager
            ->setSubscriber($this->subscriber);

        if (!$this->subscriptionsManager->isSubscribed($this->user)) {
            $this->subscriptionsManager->subscribe($this->user);

            if ($trigger) {
                //TODO: move Core/Subscriptions/Delegates
                $event = new Event();
                $event
                    ->setType('action')
                    ->setAction('subscribe')
                    ->setProduct('platform')
                    ->setUserGuid((string) $this->subscriber->guid)
                    ->setUserPhoneNumberHash($this->subscriber->getPhoneNumberHash())
                    ->setEntityGuid((string) $this->user->guid)
                    ->push();
            }
        }
    }
}
