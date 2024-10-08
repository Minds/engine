<?php

/**
 * Subscriptions Manager
 *
 * @author emi / mark
 */

namespace Minds\Core\Payments\Subscriptions;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as CustomersManager;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class Manager
{
    public static $allowedRecurring = [
        'daily',
        'monthly',
        'yearly',
        'custom'
    ];

    /** @var Repository $repository */
    protected $repository;

    /** @var Delegates\AnalyticsDelegate */
    protected $analyticsDelegate;

    /** @var Delegates\EmailDelegate */
    protected $emailDelegate;

    /** @var Subscription $subscription */
    protected $subscription;

    /** @var User */
    protected $user;

    /** @var EntitiesBuilder */
    protected $entitiesBuilder;

    public function __construct(
        $repository = null,
        $analyticsDelegate = null,
        $emailDelegate = null,
        $entitiesBuilder = null,
        private ?CustomersManager $customersManager = null,
        private ?SubscriptionsService $stripeSubscriptionsService = null,
        private ?Config $config = null,
    ) {
        $this->repository = $repository ?: Di::_()->get('Payments\Subscriptions\Repository');
        $this->analyticsDelegate = $analyticsDelegate ?? new Delegates\AnalyticsDelegate();
        $this->emailDelegate = $emailDelegate ?? new Delegates\EmailDelegate();
        $this->entitiesBuilder = $entitiesBuilder ?? Di::_()->get('EntitiesBuilder');
        $this->customersManager ??= Di::_()->get('Stripe\Customers\ManagerV2');
        $this->stripeSubscriptionsService ??= Di::_()->get(SubscriptionsService::class);
        $this->config ??= Di::_()->get(Config::class);
    }

    /**
     * @param Subscription
     * @return Manager
     */
    public function setSubscription($subscription)
    {
        $this->subscription = $subscription;
        return $this;
    }

    /**
     * Validates whether an actor can be transact based off their account state -
     * e.g. are they banned, enabled or deleted.
     * @param string $actorType - 'recipient' or 'sender'.
     * @return boolean - true if actor can transact.
     */
    public function canTransact(string $actorType = 'sender'): bool
    {
        switch ($actorType) {
            case 'sender':
                $guid = $this->subscription->user->guid;
                break;
            case 'recipient':
                $guid = $this->subscription->getEntity()->guid;
                break;
            default:
                throw new ServerErrorException('Invalid transaction actor type');
        }

        // reconstruct from cache to ensure values are up to date.
        $user = $this->entitiesBuilder->single($guid, [
            'cache' => false,
        ]);

        return !(
            !$user ||
            $user->enabled === "no" ||
            $user->isBanned() ||
            $user->getDeleted()
        );
    }

    /**
     * @param string $id
     * @return Subscription
     */
    public function get($id)
    {
        if (strpos($id, 'sub_') === 0) {
            // Collect from Stripe
            return $this->buildSubscriptionFromStripeObject($this->stripeSubscriptionsService->retrieveSubscription($id));
        }
        return $this->repository->get($id);
    }

    /**
     * Charge
     * @return bool
     */
    public function charge()
    {
        try {
            if (!$this->canTransact('sender')) {
                throw new ServerErrorException('Cannot charge this user - they are banned, disabled or deleted');
            }

            if (!$this->canTransact('recipient')) {
                throw new ServerErrorException("Cannot pay this user - they are banned, disabled or deleted");
            }

            $result = Dispatcher::trigger('subscriptions:process', $this->subscription->getPlanId(), [
                'subscription' => $this->subscription
            ]);

            $this->subscription->setLastBilling(time());
            $this->subscription->setNextBilling($this->getNextBilling());
            // Cancel trial after subsequent charge
            $this->subscription->setTrialDays(0);
            // Successful should always reset
            $this->subscription->setStatus('active');
        } catch (\Exception $e) {
            error_log("Payment failed: " . $e->getMessage());
            $this->subscription->setStatus('failed');
        }

        $this->repository->add($this->subscription);

        //

        $this->analyticsDelegate->onCharge($this->subscription);

        return $result ?? false;
    }

    /**
     * Return a list of subscriptions
     * @param array $opts
     * @return array
     */
    public function getList(array $opts = [])
    {
        $subscriptions = $this->repository->getList($opts);
        if ($opts['user']) {
            // Hack, include stripe subscriptions
            $customer = $this->customersManager->getByUser($opts['user']);
            $stripeSubscriptions = $this->stripeSubscriptionsService->getSubscriptions(
                customerId: $customer->id,
            );
            foreach ($stripeSubscriptions as $item) {
                $subscriptions[] = $this->buildSubscriptionFromStripeObject($item);
            }
        }
        return $subscriptions;
    }

    /**
     * Build a Minds object from a stripe subscription object
     */
    private function buildSubscriptionFromStripeObject(\Stripe\Subscription $item): Subscription
    {
        $subscription = new Subscription();
        $subscription->setId($item->id)
            ->setPlanId($item->plan->nickname)
            ->setPaymentMethod('usd')
            //->setUser($opts['user']->guid)
            ->setAmount($item->plan->amount)
            ->setInterval($item->plan->interval)
            ->setLastBilling($item->current_period_start)
            ->setNextBilling($item->current_period_end)
            ->setStatus($item->status);
        return $subscription;
    }

    /////
    /// BELOW NEEDS REFACTORING TO MATCH MANAGER STYLE /MH
    /////

    /**
     * @param User $user
     * @return Manager
     */
    public function setUser(User $user)
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function create()
    {
        $this->subscription->isValid();

        $this->subscription->setNextBilling($this->getNextBilling());

        $success = (bool) $this->repository->add($this->subscription);

        //
        // Shouldn't probably trigger delegates when extending existing subscription
        $this->analyticsDelegate->onCreate($this->subscription);

        $this->emailDelegate->onCreate($this->subscription);

        return $success;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function update()
    {
        $this->subscription->isValid();

        $result = $this->repository->add($this->subscription);

        return (bool) $result;
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function cancel()
    {
        if (strpos($this->subscription->getId(), 'sub_') === 0) {
            // Cancel on stripe
            $this->stripeSubscriptionsService->cancelSubscription($this->subscription->getId());
            return;
        }

        $this->subscription->isValid();

        $this->subscription->setStatus('cancelled');

        //

        $this->analyticsDelegate->onCancel($this->subscription);

        return (bool) $this->repository->delete($this->subscription);
    }


    /**
     * @return int|null
     * @throws \Exception
     */
    public function getNextBilling()
    {
        if (!$this->subscription->getLastBilling()) {
            return null;
        }

        // TODO: Ask Mark if he is happy with this check being here
        if ($subscription = $this->get($this->subscription->getId())) {
            if ($this->subscription->getUser()->pro_expires > $subscription->getNextBilling()) {
                return $this->subscription->getUser()->pro_expires;
            }

            if ($this->subscription->getUser()->plus_expires > $subscription->getNextBilling()) {
                return $this->subscription->getUser()->plus_expires;
            }
        }


        $date = new \DateTime("@{$this->subscription->getLastBilling()}");

        if ($this->subscription->getTrialDays() > 0) {
            $date->modify("+{$this->subscription->getTrialDays()} days");
            return $date->getTimestamp();
        }

        switch ($this->subscription->getInterval()) {
            case 'daily':
                $date->modify('+1 day');
                break;
            case 'monthly':
                $date->modify('+1 month');
                break;
            case 'yearly':
                $date->modify('+1 year');
                break;
        }

        return $date->getTimestamp();
    }

    /**
     * Cancels all subscriptions from and to a User
     * @return bool
     * @throws \Exception
     */
    public function cancelAllSubscriptions()
    {
        if (!$this->user) {
            return false;
        }

        //get user's own subscriptions
        $ownSubscriptions = $this->repository
            ->getList([
                'user_guid' => $this->user->guid
            ]);

        $guid = $this->user->guid;

        //get subscriptions TO the user
        $othersSubscriptions = $this->repository->getList([
            'entity_guid' => $guid,
            'status' => 'active'
        ]);

        $subs = array_merge($ownSubscriptions, $othersSubscriptions);

        // cancel subscriptions
        foreach ($subs as $sub) {
            $this->repository->delete($sub);
        }

        return true;
    }

    /**
     * Cancels all subscriptions to a specific entity guid
     * @param string $fromGuid
     * @param string $entityGuid
     * @return bool
     */
    public function cancelSubscriptions($fromGuid, $entityGuid): bool
    {
        $subscriptions = $this->repository
            ->getList([
                'user_guid' => $fromGuid
            ]);

        foreach ($subscriptions as $subscription) {
            if ($subscription->getEntity()->getGuid() === $entityGuid) {
                $this->repository->delete($subscription);
            }
        }

        return true;
    }
}
