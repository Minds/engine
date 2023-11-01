<?php

declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Intents;

use Exception;
use Generator;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Guid;
use Minds\Core\Payments\Models\GetPaymentsOpts;
use Minds\Core\Payments\Stripe\Connect\Manager as StripeConnectManager;
use Minds\Core\Payments\Stripe\Customers\Manager as StripeCustomersManager;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\Stripe\StripeClient;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\SetupIntent as StripeSetupIntent;

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
            $customer = $this->getStripeCustomersManager()->getFromUserGuid($intent->getUserGuid());
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
            'statement_descriptor' => $intent->getStatementDescriptor(),
            'description' => $intent->getDescription()
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

        $stripeIntent = $this->getStripeClient->setupIntents->create($setupIntent->toArray());

        $intent
            ->setId($stripeIntent->id)
            ->setClientSecret($stripeIntent->client_secret);

        return $intent;
    }

    /**
     * @param string $paymentIntentId
     * @param User $sender
     * @return bool
     * @throws ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId, User $sender = null): bool
    {
        $paymentIntent = $this->getStripeClient->withUser($sender)->paymentIntents->cancel($paymentIntentId);
        return $paymentIntent->status === "canceled";
    }

    /**
     * @param string $paymentIntentId
     * @param User $sender
     * @return bool
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     * @throws StripeTransferFailedException
     * @throws Exception
     */
    public function capturePaymentIntent(string $paymentIntentId, User $sender = null): bool
    {
        $stripeClient = $this->getStripeClient->withUser($sender);
        $paymentIntent = $stripeClient->paymentIntents->retrieve($paymentIntentId);

        // is manual in this context refers to a manual transfer method rather than capture method.
        $manualTransfer = isset($paymentIntent->metadata?->is_manual_transfer) ?
            $paymentIntent->metadata?->is_manual_transfer !== 'false' :
            !$paymentIntent->transfer_data?->destination;

        $applicationFeeAmount = $stripeFutureAccount = null;
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

            $stripeFutureAccount = $this->getStripeConnectManager()->getByUser($futureAccountUser);

            if (!$stripeFutureAccount) {
                throw new UserErrorException("Stripe account not found. It may not be created yet");
            }
        }

        try {
            $paymentIntent = $stripeClient->withUser($sender)->paymentIntents->capture($paymentIntentId);

            if ($paymentIntent->status !== "succeeded") {
                return false;
            }
        } catch (ApiErrorException $e) {
            if ($e->getError()->payment_intent->status === 'succeeded') {
                return true;
            }
            throw $e;
        }

        // Was there a transfer destination? If not
        if ($manualTransfer) {
            try {
                $stripeClient->withUser($sender)->transfers->create([
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

    /**
     * Gets payment intent by payment ID as array.
     * @param string $paymentId - payment id.
     * @return array payment data.
     */
    public function getPaymentIntentByPaymentId(string $paymentId): array
    {
        return $this->getStripeClient->paymentIntents->retrieve(
            $paymentId
        )->toArray();
    }

    /**
     * Get payment intents from Stripe from opts.
     * @param GetPaymentsOpts $opts - options for API call.
     * @return array payment intents.
     */
    public function getPaymentIntents(GetPaymentsOpts $opts): array
    {
        return $this->getStripeClient->paymentIntents->all(
            $opts->export()
        )->toArray();
    }

    /**
     * Get payment intents generator from Stripe from opts.
     * @param GetPaymentsOpts $opts - options for API call.
     * @return Generator payment intents.
     */
    public function getPaymentIntentsGenerator(GetPaymentsOpts $opts): Generator
    {
        return $this->getStripeClient->paymentIntents->all(
            $opts->export()
        )->autoPagingIterator();
    }

    /**
     * Update a payment intent for Stripe.
     * @param string $paymentIntentId - payment intent id to update for.
     * @param array $payload - payload with data to update.
     * @return StripePaymentIntent Stripe payment intent object.
     */
    public function updatePaymentIntentById(string $paymentIntentId, array $payload): StripePaymentIntent
    {
        return $this->getStripeClient->paymentIntents->update(
            $paymentIntentId,
            $payload
        );
    }

    /**
     * Get payment intents by user guid.
     * @param string $userGuid - user guid to get by. Any set user guid WILL be overridden by the
     * user passed via userGuid.
     * @param GetPaymentsOpts|null $opts - payment opts.
     * @throws UserErrorException if user is not found.
     * @return array payment intents.
     */
    public function getPaymentIntentsByUserGuid(string $userGuid, GetPaymentsOpts $opts = null): array
    {
        $customer = $this->getStripeCustomersManager()->getFromUserGuid($userGuid);
        if (!$customer) {
            throw new UserErrorException("Customer was not found: $userGuid");
        }
        $opts->setCustomerId($customer->getId());
        return $this->getPaymentIntents($opts);
    }

    /**
     * Lazy load as it will try and decrypt the email on every graphql call
     */
    private function getStripeClient(): StripeClient
    {
        return $this->stripeClient ??= Di::_()->get(StripeClient::class);
    }

    /**
     * Lazy load as it will try and decrypt the email on every graphql call
     */
    private function getStripeCustomersManager(): StripeCustomersManager
    {
        return $this->stripeCustomersManager ??= new StripeCustomersManager();
    }

    /**
     * Lazy load as it will try and decrypt the email on every graphql call
     */
    private function getStripeConnectManager(): StripeConnectManager
    {
        return $this->stripeConnectManager ??= new StripeConnectManager();
    }

}
