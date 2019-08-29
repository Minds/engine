<?php

namespace Minds\Core\Payments\Stripe\Customers;

use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Instances\CustomerInstance;
use Minds\Core\Payments\Stripe\Instances\PaymentMethodInstance;

class Manager
{
    /** @var Lookup $lookup */
    private $lookup;

    private $customerInstance;

    private $paymentMethodInstance;

    public function __construct($lookup = null, $customerInstance = null, $paymentMethodInstance = null)
    {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
        $this->customerInstance = $customerInstance ?? new CustomerInstance();
        $this->paymentMethodInstance = $paymentMethodInstance ?? new PaymentMethodInstance();
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
