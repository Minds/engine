<?php

namespace Minds\Core\Payments\Stripe\PaymentMethods;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Customers\Manager as CustomersManager;
use Minds\Core\Payments\Stripe\Customers\Customer;
use Minds\Core\Payments\Stripe\Instances\PaymentMethodInstance;

class Manager
{
    /** @var Lookup $lookup */
    private $lookup;

    /** @var CustomersManager $customersManager */
    private $customersManager;

    /** @var PaymentMethodInstance $paymentMethodInstance */
    private $paymentMethodInstance;

    public function __construct(
        $lookup = null,
        $customersManager = null,
        $paymentMethodInstance = null)
    {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
        $this->customersManager = $customersManager ?? new CustomersManager;
        $this->paymentMethodInstance = $paymentMethodInstance ?? new PaymentMethodInstance();
    }

    /**
     * Return a list of payment method
     * @param array $opts
     * @return Response
     */
    public function getList(array $opts = []) : Response
    {
        if (!$opts['user_guid']) {
            throw new \Exception('User_guid not specified');
        }
        $customer = $this->customersManager->getFromUserGuid($opts['user_guid']);

        if (!$customer) {
            return new Response();
        }

        $stripePaymentMethods = $this->paymentMethodInstance->all([
            'customer' => $customer->getId(),
            'type' => 'card',
        ]);

        $response = new Response();
        foreach ($stripePaymentMethods->data as $stripePaymentMethod) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setId($stripePaymentMethod->id)
                ->setCardBrand($stripePaymentMethod->card->brand)
                ->setCardCountry($stripePaymentMethod->card->country)
                ->setCardExpires($stripePaymentMethod->card->exp_month . '/' . $stripePaymentMethod->card->exp_year)
                ->setCardLast4($stripePaymentMethod->card->last4);
            $response[] = $paymentMethod;
        }
        return $response;
    }

    /**
     * Add a payment method to stripe
     * @param Customer $customer
     * @return boolean
     */
    public function add(PaymentMethod $paymentMethod) : PaymentMethod
    {
        if ($paymentMethod->getCustomerId()) {
            $stripePaymentMethod = $this->paymentMethodInstance->retrieve($paymentMethod->getId());
            $stripePaymentMethod->attach([ 'customer' => $paymentMethod->getCustomerId() ]);
        } else {
            $customer = new Customer();
            $customer->setPaymentMethod($paymentMethod->getId())
                ->setUserGuid($paymentMethod->getUserGuid());
            $customer = $this->customersManager->add($customer);
            $paymentMethod->setCustomerId($customer->getId());
        }

        return $paymentMethod;
    }

    /**
     * Delete a payment method
     * @param string $id
     * @return bool
     */
    public function delete($id) : bool
    {
        $stripePaymentMethod = $this->paymentMethodInstance->retrieve($id);
        return (bool) $stripePaymentMethod->detach();
    }
}
