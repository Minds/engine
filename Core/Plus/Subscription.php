<?php

namespace Minds\Core\Plus;

use Minds\Core\Di\Di;
use Minds\Core\Payments;
use Minds\Core\Payments\Subscriptions;
use Minds\Entities\User;
use Minds\Core\Payments\Subscriptions\Manager;
use Minds\Core\Payments\Subscriptions\Repository;

class Subscription
{
    /** @var Config */
    private $config;
    private $stripe;
    private $repo;
    /** @var User */
    protected $user;
    /** @var Manager $subscriptionsManager */
    protected $subscriptionsManager;
    /** @var Repository $subscriptionsRepository */
    protected $subscriptionsRepository;

    public function __construct(
        $config = null,
        $stripe = null,
        $subscriptionsManager = null,
        $subscriptionsRepository = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->stripe = $stripe ?: Di::_()->get('StripePayments');
        $this->subscriptionsManager = $subscriptionsManager ?: Di::_()->get('Payments\Subscriptions\Manager');
        $this->subscriptionsRepository = $subscriptionsRepository ?: Di::_()->get('Payments\Subscriptions\Repository');
    }

    /**
     * @param $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->user->isPlus();
    }

    /**
     * @return bool
     */
    public function canBeCancelled()
    {
        return ((int) $this->user->plus_expires) > time();
    }

    /**
     * @param $subscription
     * @return $this
     * @throws \Exception
     */
    public function create($subscription)
    {
        $subscription->setInterval('monthly')
            ->setAmount(5);

        $this->subscriptionsManager
            ->setSubscription($subscription)
            ->create();

        return $this;
    }

    public function cancel()
    {
        try {
            $this->subscriptionsManager->cancelSubscriptions($this->user->guid, $this->config->get('plus')['handler']);
        } catch (\Exception $e) {
        }

        return $this;
    }

    /**
     * @return bool
     */
    public function hasSubscriptions(): bool
    {
        $subscriptions = $this->subscriptionsManager->getList([
            'user_guid' => $this->user->guid,
            'entity_guid' => $this->config->get('plus')['handler']
        ]);

        return count($subscriptions) > 0;
    }
}
