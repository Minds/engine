<?php

declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Intents;

use Exception;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Payments\Stripe\Connect\Manager as StripeConnectManager;
use Minds\Core\Payments\Stripe\Customers\Manager as StripeCustomersManager;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;
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
        private ?MindsConfig            $mindsConfig = null,
        private ?StripeConnectManager   $stripeConnectManager = null,
        private ?EntitiesBuilder        $entitiesBuilder = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->stripeClient ??= new StripeClient($this->mindsConfig->get('payments')['stripe']['api_key']);
        $this->stripeCustomersManager ??= new StripeCustomersManager();
        $this->stripeConnectManager ??= new StripeConnectManager();
        $this->entitiesBuilder ??= Di::_()->get('EntitiesBuilder');
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
     * @throws ApiErrorException
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

        $paymentIntentDetails = $this->prepareStripePaymentIntent($intent);

        $stripeIntent = StripePaymentIntent::create($paymentIntentDetails);

        $intent->setId($stripeIntent->id);
        return $intent;
    }

    private function prepareStripePaymentIntent(PaymentIntent $intent): array
    {
        $intentData = [
            'amount' => $intent->getAmount(),
            'currency' => $intent->getCurrency(),
            'customer' => $intent->getCustomerId(),
            'payment_method' => $intent->getPaymentMethod(),
            'confirmation_method' => 'automatic',
            'confirm' => true,
            'off_session' => true,
            'capture_method' => $intent->getCaptureMethod(),
            'metadata' => $intent->getMetadata(),
            'payment_method_types' => [
                'card'
            ],
            'statement_descriptor' => $intent->getDescriptor()
        ];

        if ($intent->getStripeAccountId()) {
            // If there is already an account setup with Stripe Connect
            $intentData['on_behalf_of'] = $intent->getStripeAccountId();
            $intentData['application_fee_amount'] = $intent->getServiceFee();
            $intentData['transfer_data'] = [
                'destination' => $intent->getStripeAccountId(),
            ];
        } else {
            // If there is not yet an account setup with Stripe Connect
            $intentData['transfer_group'] = $intent->getStripeFutureAccountGuid() . '-' . Guid::build(); // TODO
            $intentData['metadata']['future_account_guid'] = $intent->getStripeFutureAccountGuid();
            $intentData['metadata']['application_fee_amount'] = $intent->getServiceFee();
        }

        return $intentData;
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
     * @throws ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId): bool
    {
        $paymentIntent = $this->stripeClient->paymentIntents->cancel($paymentIntentId);

        return $paymentIntent->status === "canceled";
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     * @throws StripeTransferFailedException
     * @throws Exception
     */
    public function capturePaymentIntent(string $paymentIntentId): bool
    {
        $paymentIntent = $this->stripeClient->paymentIntents->retrieve($paymentIntentId);

        $manualTransfer = !$paymentIntent->transfer_data?->destination;

        $applicationFeeAmount = $futureAccountGuid = null;
        if ($manualTransfer) {
            // If no destination was set, the we expect that the payment intent has a meta field
            // call 'future_account_guid' that we will fetch the user for
            $futureAccountGuid = $paymentIntent->metadata?->future_account_guid;
            $applicationFeeAmount = $paymentIntent->metadata?->application_fee_amount;

            // Grab the user entity of the futureAccount
            $futureAccountUser = $this->entitiesBuilder->single($futureAccountGuid);
            if (!$futureAccountUser instanceof User) {
                throw new ServerErrorException("Unable to find user for future account payment");
            }

            $stripeFutureAccount = $this->stripeConnectManager->getByUser($futureAccountUser);

            if (!$stripeFutureAccount) {
                throw new UserErrorException("Stripe account not found. It may not be created yet");
            }
        }
        
        $paymentIntent = $this->stripeClient->paymentIntents->capture($paymentIntentId);

        if ($paymentIntent->status !== "succeeded") {
            return false;
        }

        // Was there a transfer destination? If not
        if ($manualTransfer) {
            try {
                $this->stripeClient->transfers->create([
                    'amount' => $paymentIntent->amount - $applicationFeeAmount,
                    'currency' => 'usd',
                    'destination' => $stripeFutureAccount?->getId(),
                    'transfer_group' => $paymentIntent->transfer_group,
                    'source_transaction' => $paymentIntent->charges->data[0]->id
                ]);
            } catch (ApiErrorException $e) {
                error_log($e->getMessage());
                error_log($e->getTraceAsString());
                throw new StripeTransferFailedException();
            }
        }

        return true;
    }
}
