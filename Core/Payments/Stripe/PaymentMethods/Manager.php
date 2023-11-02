<?php

namespace Minds\Core\Payments\Stripe\PaymentMethods;

use Minds\Common\Repository\Response;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Customers\Customer;
use Minds\Core\Payments\Stripe\Customers\Manager as CustomersManager;
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
        $paymentMethodInstance = null
    ) {
        $this->lookup = $lookup ?: Di::_()->get('Database\Cassandra\Lookup');
        $this->customersManager = $customersManager;
        $this->paymentMethodInstance = $paymentMethodInstance;
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
        $customer = $this->getCustomersManager()->getFromUserGuid($opts['user_guid']);

        if (!$customer) {
            return new Response();
        }

        $stripePaymentMethods = $this->getPaymentMethodInstance()->all([
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
     * @param string $userGuid
     * @param string $paymentMethodId
     * @return bool
     * @throws \Exception
     */
    public function checkPaymentMethodOwnership(string $userGuid, string $paymentMethodId): bool
    {
        $customerDetails = $this->getCustomersManager()->getFromUserGuid($userGuid);
        if (!$customerDetails) {
            return false;
        }

        $paymentMethods = $this->getList(['user_guid' => $userGuid]);

        $results = array_filter(
            $paymentMethods->toArray(),
            function (PaymentMethod $item) use ($paymentMethodId): bool {
                return $item->getId() === $paymentMethodId;
            }
        );
        return count($results) > 0;
    }

    /**
     * Add a payment method to stripe
     * @param Customer $customer
     * @return boolean
     */
    public function add(PaymentMethod $paymentMethod) : PaymentMethod
    {
        if ($paymentMethod->getCustomerId()) {
            $stripePaymentMethod = $this->getPaymentMethodInstance()->retrieve($paymentMethod->getId());
            $stripePaymentMethod->attach([ 'customer' => $paymentMethod->getCustomerId() ]);
        } else {
            $customer = new Customer();
            $customer->setPaymentMethod($paymentMethod->getId())
                ->setUserGuid($paymentMethod->getUserGuid());
            $customer = $this->getCustomersManager()->add($customer);
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
        $stripePaymentMethod = $this->getPaymentMethodInstance()->retrieve($id);
        return (bool) $stripePaymentMethod->detach();
    }

    /**
     * Lazy load for performance
     */
    private function getCustomersManager(): CustomersManager
    {
        return $this->customersManager ??= new CustomersManager;
    }

    /**
     * Lazy load for performance
     */
    private function getPaymentMethodInstance(): PaymentMethodInstance
    {
        return $this->paymentMethodInstance ??= new PaymentMethodInstance();
    }
}
