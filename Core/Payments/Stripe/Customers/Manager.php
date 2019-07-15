<?php

namespace Minds\Core\Payments\Stripe\Customers;

use Minds\Core\Di\Di;

class Manager
{

    /** @var Lookup $lookup */
    private $lookup;

    public function __construct($lookup = null)
    {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
    }

    /**
     * Return a Customer from user guid
     * @param string $userGuid
     * @return Customer
     */
    public function getFromUserGuid($userGuid): Customer
    {
        $customerId = $this->lookup->get("{$userGuid}:payments")['customer_id'];

        if (!$customerId) {
            return null;
        }

        $stripeCustomer = \Stripe\Customer::retrieve($customerId);

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
     * Add a customer to stripe
     * @param Customer $customer
     * @return boolean
     */
    public function add(Customer $customer)
    {
        $customer = \Stripe\Customer::create([
            'source' => $customer->getPaymentSource(),
            'email' => $customer->getUser()->getEmail(),
        ]);

        $this->lu->set("{$customer->getUserGuid()}:payments", [
            'customer_id' => (string) $customer->id
        ]);

        return (bool) $customer->id;
    }

}