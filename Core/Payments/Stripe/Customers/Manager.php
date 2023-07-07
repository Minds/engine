<?php

namespace Minds\Core\Payments\Stripe\Customers;

use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Instances\CustomerInstance;
use Minds\Entities\User;

class Manager
{
    /** @var Lookup $lookup */
    private $lookup;

    private $customerInstance;

    public function __construct(
        $lookup = null,
        $customerInstance = null,
        private ?EntitiesBuilder $entitiesBuilder = null
    ) {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
        $this->customerInstance = $customerInstance ?? new CustomerInstance();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
    }

    /**
     * Return a Customer from user guid
     * @param string $userGuid
     * @return Customer
     */
    public function getFromUserGuid($userGuid): ?Customer
    {
        $customerId = $this->lookup->get("{$userGuid}:payments")['customer_id'];

        if (!$customerId) {
            return null;
        }

        $user = $this->entitiesBuilder->single($userGuid);
        if (!$user || !($user instanceof User)) {
            return null;
        }

        $stripeCustomer = $this->customerInstance->withUser($user)
            ->retrieve($customerId);

        if (!$stripeCustomer) {
            return null;
        }

        $customer = new Customer();
        $customer->setPaymentSource($stripeCustomer->default_source)
            ->setUserGuid($userGuid)
            ->setId($customerId);

        return $customer;
    }

    /**
     * Return a Customer from user entity
     * @param User $user
     * @return Customer
     */
    public function getFromUser(User $user): ?Customer
    {
        $customerId = $this->lookup->get("{$user->getGuid()}:payments")['customer_id'];

        if (!$customerId) {
            return null;
        }

        $stripeCustomer = $this->customerInstance->withUser($user)
            ->retrieve($customerId);

        if (!$stripeCustomer) {
            return null;
        }

        $customer = new Customer();
        $customer->setPaymentSource($stripeCustomer->default_source)
            ->setUserGuid($user->Guid)
            ->setId($customerId);

        return $customer;
    }

    /**
     * Add a customer to stripe
     * @param Customer $customer
     * @return boolean
     */
    public function add(Customer $customer) : Customer
    {
        $stripeCustomer = $this->customerInstance->create([
            'payment_method' => $customer->getPaymentMethod(),
        ]);

        $this->lookup->set("{$customer->getUserGuid()}:payments", [
            'customer_id' => (string) $stripeCustomer->id
        ]);

        $customer->setId($stripeCustomer->id);

        return $customer;
    }
}
