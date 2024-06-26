<?php

namespace Minds\Core\Payments\Stripe\Intents;

use Minds\Core\Payments\Stripe\Customers\Manager as CustomersManager;
use Minds\Core\Payments\Stripe\Instances\PaymentIntentInstance;
use Minds\Core\Payments\Stripe\Instances\SetupIntentInstance;
use Minds\Exceptions\UserErrorException;

class Manager
{
    /** @var CustomersManager $customersManager */
    private $customersManager;

    /** @var PaymentIntentInstance $paymentIntentInstance */
    private $paymentIntentInstance;

    /** @var SetupIntentInstance $setupIntentInstance */
    private $setupIntentInstance;

    public function __construct(
        CustomersManager $customersManager = null,
        PaymentIntentInstance $paymentIntentInstance = null,
        SetupIntentInstance $setupIntentInstance = null
    ) {
        $this->customersManager = $customersManager ?? new CustomersManager;
        $this->paymentIntentInstance = $paymentIntentInstance ?: new PaymentIntentInstance();
        $this->setupIntentInstance = $setupIntentInstance ?: new SetupIntentInstance();
    }

    /**
     * Add a payment intent to stripe
     * @param Intent $intent
     * @return Intent
     */
    public function add(Intent $intent): Intent
    {
        switch (true) {
            case $intent instanceof PaymentIntent:
                return $this->addPayment($intent);
                break;
            case $intent instanceof SetupIntent:
                return $this->addSetup($intent);
                break;
            default:
                throw new \Exception('Intent not supported or not an intent');
        }
    }

    private function addPayment(PaymentIntent $intent) : PaymentIntent
    {
        $customerId = $intent->getCustomerId();
        if (!$customerId) {
            $customer = $this->customersManager->getFromUserGuid($intent->getUserGuid());
            if (!$customer) {
                throw new UserErrorException('Customer was not found');
            }
            $customerId = $customer->getId();
        }
        $params = [
            'amount' => $intent->getAmount(),
            'currency' => $intent->getCurrency(),
            'payment_method_types' => [
                'card',
            ],
            'customer' => $customerId,
            'payment_method' => $intent->getPaymentMethod(),
            'off_session' => $intent->isOffSession(),
            'confirm' => $intent->isConfirm(),
            'capture_method' => $intent->getCaptureMethod(),
            'on_behalf_of' => $intent->getStripeAccountId(),
            'transfer_data' => [
                'destination' => $intent->getStripeAccountId(),
            ],
            'metadata' => [
                'user_guid' => $intent->getUserGuid(),
            ],
            'statement_descriptor_suffix' => $intent->getStatementDescriptor(),
            'description' => $intent->getDescription(),
        ];

        if ($intent->getMetadata()) {
            $params['metadata'] = $intent->getMetadata();
        } else {
            $params['metadata'] = [
                'user_guid' => $intent->getUserGuid()
            ];
        }

        if ($intent->getServiceFee()) {
            $params['application_fee_amount'] = $intent->getServiceFee();
        }

        $stripeIntent = $this->paymentIntentInstance->create($params);

        $intent->setId($stripeIntent->id);

        return $intent;
    }

    private function addSetup(SetupIntent $intent) : SetupIntent
    {
        $params = [
            'usage' => 'off_session',
        ];

        $stripeIntent = $this->setupIntentInstance->create($params);

        $intent->setId($stripeIntent->id)
            ->setClientSecret($stripeIntent->client_secret);
        
        return $intent;
    }

    /**
     * Return an intent
     * @param string $id
     * @return SetupIntent
     */
    public function get(string $id) : SetupIntent
    {
        $stripeIntent = $this->setupIntentInstance->retrieve($id);

        $intent = new SetupIntent();
        $intent->setId($id)
            ->setPaymentMethod($stripeIntent->payment_method);

        return $intent;
    }
}
