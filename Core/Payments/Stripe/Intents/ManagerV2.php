<?php

namespace Minds\Core\Payments\Stripe\Intents;

use Exception;
use Minds\Core\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Payments\Stripe\Customers\Manager as StripeCustomersManager;
use Minds\Exceptions\UserErrorException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\SetupIntent as StripeSetupIntent;
use Stripe\StripeClient;

/**
 * Manager for Stripe intents (Payment, Setup)
 */
class ManagerV2
{
    public function __construct(
        private ?StripeClient           $stripeClient = null,
        private ?StripeCustomersManager $stripeCustomersManager = null,
        private ?MindsConfig            $mindsConfig = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->stripeClient ??= new StripeClient($this->mindsConfig->get('payments')['stripe']['api_key']);
        $this->stripeCustomersManager ??= new StripeCustomersManager();
    }

    /**
     * @param Intent $intent
     * @return Intent
     * @throws Exception
     */
    public function add(Intent $intent): Intent
    {
        return match (true) {
            $intent instanceof PaymentIntent => $this->addPaymentIntent($intent),
            $intent instanceof SetupIntent => $this->addSetupIntent($intent),
            default => throw new Exception("Intent not supported or not an intent")
        };
    }

    /**
     * @param PaymentIntent $intent
     * @return PaymentIntent
     * @throws UserErrorException
     * @throws \Stripe\Exception\ApiErrorException
     */
    private function addPaymentIntent(PaymentIntent $intent): PaymentIntent
    {
        $customerId = $intent->getCustomerId();
        if (!$customerId) {
            $customer = $this->stripeCustomersManager->getFromUserGuid($intent->getUserGuid());
            if (!$customer) {
                throw new UserErrorException('Customer was not found');
            }
            $customerId = $customer->getId();
        }

        $intent->setCustomerId($customerId);

        $paymentIntent = $this->prepareStripePaymentIntent($intent);

        $stripeIntent = $this->stripeClient->paymentIntents->create($paymentIntent->toArray());

        $intent->setId($stripeIntent->id);
        return $intent;
    }

    private function prepareStripePaymentIntent(PaymentIntent $intent): StripePaymentIntent
    {
        $paymentIntent = new StripePaymentIntent();
        $paymentIntent->amount = $intent->getAmount();
        $paymentIntent->currency = $intent->getCurrency();
        $paymentIntent->customer = $intent->getCustomerId();
        $paymentIntent->payment_method = $intent->getPaymentMethod();
        $paymentIntent->setup_future_usage = $intent->isOffSession();
        $paymentIntent->capture_method = $intent->getCaptureMethod();
        $paymentIntent->on_behalf_of = $intent->getStripeAccountId();
        $paymentIntent->transfer_data = [
            'destination' => $intent->getStripeAccountId()
        ];
        $paymentIntent->payment_method_types = [
            'card'
        ];
        $paymentIntent->metadata = $intent->getMetadata();

        return $paymentIntent;
    }

    private function addSetupIntent(SetupIntent $intent): SetupIntent
    {
        $setupIntent = new StripeSetupIntent();
        $setupIntent->usage = 'off_session';

        $stripeIntent = $this->stripeClient->setupIntents->create($setupIntent->toArray());

        $intent
            ->setId($stripeIntent->id)
            ->setClientSecret($stripeIntent->client_secret);

        return $intent;
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId): bool
    {
        $paymentIntent = $this->stripeClient->paymentIntents->cancel($paymentIntentId);

        return $paymentIntent->status === "canceled";
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function capturePaymentIntent(string $paymentIntentId): bool
    {
        $paymentIntent = $this->stripeClient->paymentIntents->capture($paymentIntentId);

        return $paymentIntent->status === "succeeded";
    }
}
