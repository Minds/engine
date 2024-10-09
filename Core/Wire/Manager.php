<?php

/**
 * Created by Marcelo.
 * Date: 03/07/2017.
 */

namespace Minds\Core\Wire;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Guid;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardInsufficientFundsException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardNotFoundException;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\Stripe\Intents\Intent;
use Minds\Core\Payments\Stripe\Intents\Manager as StripeIntentsManager;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire\Exceptions\RemoteUserException;
use Minds\Core\Wire\Exceptions\WalletNotSetupException;
use Minds\Core\Wire\SupportTiers\Manager as SupportTiersManager;
use Minds\Entities;
use Minds\Entities\Activity;
use Minds\Entities\Enums\FederatedEntitySourcesEnum;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as CustomersManager;
use Minds\Core\Payments\Stripe\StripeApiKeyConfig;
use Minds\Exceptions\UserErrorException;
use Stripe\Subscription;

class Manager
{
    /** @var Repository */
    protected $repository;

    /** @var Core\Blockchain\Transactions\Manager */
    protected $txManager;

    /** @var Core\Blockchain\Transactions\Repository */
    protected $txRepo;

    /** @var Entities\User $sender */
    protected $sender;

    /** @var Entities\User $receiver */
    protected $receiver;

    /** @var Entities\Entity $entity */
    protected $entity;

    /** @var float $amount */
    protected $amount;

    /** @var bool $recurring */
    protected $recurring;

    /** @var string $recurringInterval */
    protected $recurringInterval;

    /** @var array $payload */
    protected $payload;

    private ?Activity $sourceEntity = null;

    /** @var Core\Config */
    protected $config;

    /** @var Core\Blockchain\Services\Ethereum */
    protected $client;

    /** @var Core\Blockchain\Token */
    protected $token;

    /** @var Core\Blockchain\Wallets\OffChain\Cap $cap */
    protected $cap;

    /** @var Delegates\UpgradesDelegate */
    protected $upgradesDelegate;

    /** @var Delegates\RecurringDelegate $recurringDelegate */
    protected $recurringDelegate;

    /** @var Delegates\NotificationDelegate $notificationDelegate */
    protected $notificationDelegate;

    /** @var Delegates\CacheDelete $cacheDelegate */
    protected $cacheDelegate;

    /** @var Core\Blockchain\Wallets\OffChain\Transactions */
    protected $offchainTxs;

    /** @var StripeIntentsManager $stripeIntentsManager */
    protected $stripeIntentsManager;

    /** @var Core\Security\ACL */
    protected $acl;

    /** @var Delegates\EventsDelegate */
    protected $eventsDelegate;

    /** @var int */
    const WIRE_SERVICE_FEE_PCT = 10;

    /** @var int */
    const TRIAL_DAYS = 7;

    /** @var int */
    const TRIAL_THRESHOLD_DAYS = 90;

    public function __construct(
        $repository = null,
        $txManager = null,
        $txRepo = null,
        $config = null,
        $client = null,
        $token = null,
        $cap = null,
        $upgradesDelegate = null,
        $recurringDelegate = null,
        $notificationDelegate = null,
        $cacheDelegate = null,
        $offchainTxs = null,
        $stripeIntentsManager = null,
        $acl = null,
        $eventsDelegate = null,
        private ?SupportTiersManager $supportTiersManager = null,
        private ?PaymentsManager $paymentsManager = null,
        private readonly ?GiftCardsManager $giftCardsManager = null,
        private readonly ?SubscriptionsService $stripeSubscriptionsService = null,
        private readonly ?CustomersManager $customersManager = null,
        private readonly ?StripeApiKeyConfig $stripeApiKeyConfig = null,
    ) {
        $this->repository = $repository ?: Di::_()->get('Wire\Repository');
        $this->txManager = $txManager ?: Di::_()->get('Blockchain\Transactions\Manager');
        $this->txRepo = $txRepo ?: Di::_()->get('Blockchain\Transactions\Repository');
        $this->config = $config ?: Di::_()->get('Config');
        $this->client = $client ?: Di::_()->get('Blockchain\Services\Ethereum');
        $this->token = $token ?: Di::_()->get('Blockchain\Token');
        $this->cap = $cap ?: Di::_()->get('Blockchain\Wallets\OffChain\Cap');
        $this->upgradesDelegate = $upgradesDelegate ?? new Delegates\UpgradesDelegate();
        $this->recurringDelegate = $recurringDelegate ?: new Delegates\RecurringDelegate();
        $this->notificationDelegate = $notificationDelegate ?: new Delegates\NotificationDelegate();
        $this->cacheDelegate = $cacheDelegate ?: new Delegates\CacheDelegate();
        $this->offchainTxs = $offchainTxs ?: new Core\Blockchain\Wallets\OffChain\Transactions();
        $this->stripeIntentsManager = $stripeIntentsManager ?? Di::_()->get('Stripe\Intents\Manager');
        $this->acl = $acl ?: Core\Security\ACL::_();
        $this->eventsDelegate = $eventsDelegate ?? new Delegates\EventsDelegate();
        $this->supportTiersManager ??= Di::_()->get('Wire\SupportTiers\Manager');
        $this->paymentsManager ??= Di::_()->get(PaymentsManager::class);
    }

    /**
     * @param string $urn
     * @return Wire
     */
    public function getByUrn(string $urn): ?Wire
    {
        return $this->repository->get($urn);
    }

    /**
     * Set the sender of the wire.
     *
     * @param User $sender
     *
     * @return $this
     */
    public function setSender($sender)
    {
        $this->sender = $sender;

        return $this;
    }

    /**
     * Set the entity of the wire - will also set the receiver.
     *
     * @param Entity $entity
     *
     * @return $this
     */
    public function setEntity($entity)
    {
        if (!is_object($entity)) {
            $entity = Entities\Factory::build($entity);
        }

        $this->receiver = $entity->type != 'user' ?
            Entities\Factory::build($entity->owner_guid) :
            $entity;

        $this->entity = $entity;

        return $this;
    }

    public function setSourceEntity(Activity $activity): self
    {
        $this->sourceEntity = $activity;
        return $this;
    }

    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    public function setRecurring($recurring)
    {
        $this->recurring = $recurring;

        return $this;
    }

    /**
     * Recurring interval
     * @param string $interval
     * @return self
     */
    public function setRecurringInterval(string $interval): self
    {
        $this->recurringInterval = $interval;
        return $this;
    }

    /**
     * Set the payload of the transaction.
     *
     * @param array $payload
     *
     * @return $this
     */
    public function setPayload($payload = [])
    {
        $this->payload = $payload;

        return $this;
    }

    /**
     * @return bool
     *
     * @throws WalletNotSetupException
     * @throws \Exception
     */
    public function create(): bool
    {
        if ($this->payload['method'] == 'onchain' && (!$this->receiver->getEthWallet() || $this->receiver->getEthWallet() != $this->payload['receiver'])) {
            throw new WalletNotSetupException();
        }

        if ($this->receiver->getSource() !== FederatedEntitySourcesEnum::LOCAL) {
            throw new RemoteUserException();
        }

        $wire = new Wire();
        $wire
            ->setSender($this->sender)
            ->setReceiver($this->receiver)
            ->setEntity($this->entity)
            ->setAmount($this->amount)
            ->setTimestamp(time())
            ->setRecurringInterval($this->recurringInterval);

        $isProPayment = false;
        $isPlusPayment = false;

        // If receiver is handler for Minds+/Pro, bypass the ACL
        $bypassAcl = false;
        if ($this->isPlusReceiver((string) $this->receiver->getGuid())) {
            $isPlusPayment = true;
            $bypassAcl = true;
        }

        if ($this->isProReceiver((string) $this->receiver->getGuid())) {
            $isProPayment = true;
            $bypassAcl = true;
        }

        if (!$bypassAcl && !$this->acl->write($wire)) {
            return false;
        }

        switch ($this->payload['method']) {
            case 'onchain':
                //add transaction to the senders transaction log
                $transaction = new Core\Blockchain\Transactions\Transaction();
                $transaction
                    ->setUserGuid($this->sender->guid)
                    ->setWalletAddress($this->payload['address'])
                    ->setContract('wire')
                    ->setTx($this->payload['txHash'])
                    ->setAmount((string) BigNumber::_($this->amount)->neg())
                    ->setTimestamp(time())
                    ->setCompleted(false)
                    ->setData([
                        'amount' => (string) $this->amount,
                        'receiver_address' => $this->payload['receiver'],
                        'sender_address' => $this->payload['address'],
                        'receiver_guid' => (string) $this->receiver->guid,
                        'sender_guid' => (string) $this->sender->guid,
                        'entity_guid' => (string) $this->entity->guid,
                    ]);
                $this->txManager->add($transaction);

                $wire->setAddress($this->payload['address']);

                break;
            case 'offchain':
                /* @var Core\Blockchain\Wallets\OffChain\Cap $cap */
                $this->cap
                    ->setUser($this->sender)
                    ->setContract('wire');

                if (!$this->cap->isAllowed($this->amount) && !$bypassAcl) {
                    throw new \Exception('You are not allowed to spend that amount of coins.');
                }

                $txData = [
                    'amount' => (string) $this->amount,
                    'sender_guid' => (string) $this->sender->guid,
                    'receiver_guid' => (string) $this->receiver->guid,
                    'entity_guid' => (string) $this->entity->guid,
                ];

                // Charge offchain wallet
                $this->offchainTxs
                    ->setAmount($this->amount)
                    ->setType('wire')
                    ->setUser($this->receiver)
                    ->setData($txData)
                    ->transferFrom($this->sender);

                // Save the wire to the Repository
                $this->repository->add($wire);

                $wire->setAddress('offchain');

                // Notify plus/pro
                $this->upgradesDelegate
                    ->onWire($wire, 'offchain');

                // Submit action event
                $this->eventsDelegate->onAdd($wire);

                // Send notification
                $this->notificationDelegate->onAdd($wire);

                // Clear caches
                $this->cacheDelegate->onAdd($wire);

                break;
            case 'erc20':
                throw new \Exception("Not implemented ERC20 yet");
                break;
            case 'eth':
                throw new \Exception("Not implemented ETH yet");
                break;
            case 'usd':
                if (!$this->receiver->getMerchant() || !$this->receiver->getMerchant()['id']) {
                    throw new \Exception("This channel is not able to receive USD at the moment");
                }
                if (!empty($this->receiver->getNsfw())) {
                    throw new \Exception("This channel cannot receive USD due to being flagged as NSFW");
                }
                if (!$this->payload['paymentMethodId']) {
                    throw new \Exception("You must select a payment method");
                }

                $wire->setAddress('stripe')
                    ->setMethod('usd');

                $paymentMethod = "usd";
                if ($this->payload['paymentMethodId'] === GiftCard::DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID) {
                    $paymentMethod = GiftCard::DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID;
                }

                // Determine if a trial is eligible
                // If the reciever is Minds+ channel and the sender has never had plus (no plus_expires field)
                // or they haven't had plus in 90 days then they will have a trial.
                $canHavePlusTrial = !$this->sender->plus_expires || $this->sender->plus_expires <= strtotime(self::TRIAL_THRESHOLD_DAYS . ' days ago');
                if ($paymentMethod === "usd" && $this->receiver->getGuid() == $this->config->get('plus')['handler'] && $canHavePlusTrial) { // No trial provided if purchased via gift card credits
                    $wire->setTrialDays(self::TRIAL_DAYS);
                }

                if ($paymentMethod === "usd") {
                    $intent = $this->processStripeWirePayment($wire);

                    $transactionId = $intent instanceof Subscription ? $intent->id : $intent->getId();
                } else {
                    $transactionId = GiftCard::DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID;
                }
                
                // Add to Minds payments table
                $paymentDetails = $this->paymentsManager->createPaymentFromWire(
                    wire: $wire,
                    paymentTxId: $transactionId,
                    isPlus: $isPlusPayment,
                    isPro: $isProPayment,
                    sourceActivity: $this->sourceEntity,
                    paidWithGiftCard: $paymentMethod === GiftCard::DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID
                );

                if (
                    $paymentMethod === GiftCard::DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID &&
                    ($isPlusPayment || $isProPayment)
                ) {
                    $this->processGiftCardWirePayment(
                        paymentDetails: $paymentDetails,
                        isPlusPayment: $isPlusPayment
                    );
                }

                // Save the wire to the Repository
                $wire->setPaymentGuid($paymentDetails->paymentGuid);
                $this->repository->add($wire);

                // Notify plus/pro
                $this->upgradesDelegate
                    ->onWire($wire, 'usd');

                // Submit action event
                $this->eventsDelegate->onAdd($wire);

                // Send notification
                $this->notificationDelegate->onAdd($wire);

                // Clear caches
                $this->cacheDelegate->onAdd($wire);
                break;
        }

        if ($this->recurring) {
            $this->recurringDelegate->onAdd($wire);
        }

        return true;
    }

    /**
     * @param Wire $wire
     * @return Intent
     * @throws \Exception
     */
    private function processStripeWirePayment(Wire $wire): Intent|Subscription
    {
        $statementDescriptor = $this->getStatementDescriptorFromWire($wire);
        $description = $this->getDescriptionFromWire($wire);

        // Setup a stripe subscription for Plus & Pro subscriptions ONLY
        if ($this->isPlusReceiver($this->receiver->getGuid()) || $this->isProReceiver($this->receiver->getGuid())) {
            $this->recurring = false; // Do not do recurring as we will use a stripe subscription instead

            // Are we in test mode? We use different product and price id's in case
            $isTestMode = false;
            if ($this->stripeApiKeyConfig->shouldUseTestMode($this->sender)) {
                $isTestMode = true;
            }

            $productKey = $this->isPlusReceiver($this->receiver->getGuid()) ? 'plus' : 'pro';
            $stripePriceId = $this->config->get('upgrades')[$productKey][$wire->getRecurringInterval() ?: 'monthly'][$isTestMode ? 'stripe_price_id_test' : 'stripe_price_id'];
            $stripeProductId = $this->config->get('upgrades')[$productKey][$isTestMode ? 'stripe_product_id_test' : 'stripe_product_id'];

            $items = [
                [
                    'price' => $stripePriceId
                ]
            ];

            $customer = $this->customersManager->getByUser($this->sender);

            // Before we make a new subscription, confirm a subscription doesn't already exist
            $existingSubscriptions = $this->stripeSubscriptionsService->getSubscriptions(
                customerId: $customer->id,
                status: 'active'
            );
            foreach ($existingSubscriptions as $existingSubscription) {
                if ($existingSubscription->plan->product === $stripeProductId) {
                    throw new UserErrorException("You already have an active subscription to this product");
                }
            }

            $subscription = $this->stripeSubscriptionsService->createSubscription(
                customerId: $customer->id,
                paymentMethodId: $this->payload['paymentMethodId'],
                items: $items,
                trialDays: (int) $wire->getTrialDays(),
                metadata: [
                    'user_guid' => $this->sender->getGuid(),
                ],
            );
            return $subscription;
        }

        // If this is a trial, we still create the subscription but do not charge
        $intent = new PaymentIntent();
        $intent
            ->setUserGuid($this->sender->getGuid())
            ->setAmount(!$wire->getTrialDays() ? $this->amount : 100) // $1 hold on card during trial
            ->setPaymentMethod($this->payload['paymentMethodId'])
            ->setOffSession(true)
            ->setConfirm(true)
            ->setCaptureMethod(!$wire->getTrialDays() ? 'automatic' : 'manual') // Do not charge card
            ->setStripeAccountId($this->receiver->getMerchant()['id'])
            ->setServiceFeePct(static::WIRE_SERVICE_FEE_PCT)
            ->setMetadata([
                'user_guid' => $this->sender->getGuid(),
                'receiver_guid' => $this->receiver->getGuid()
            ])
            ->setStatementDescriptor($statementDescriptor)
            ->setDescription($description);

        // Charge stripe
        $intent = $this->stripeIntentsManager->add($intent);

        if (!$intent->getId()) {
            throw new \Exception("Payment failed");
        }

        return $intent;
    }

    /**
     * @param PaymentDetails $paymentDetails
     * @param bool $isPlusPayment
     * @return bool
     * @throws GiftCardInsufficientFundsException
     * @throws GiftCardNotFoundException
     * @throws ServerErrorException
     */
    private function processGiftCardWirePayment(
        PaymentDetails $paymentDetails,
        bool $isPlusPayment
    ): void {
        $this->giftCardsManager->spend(
            user: $this->sender,
            productId: $isPlusPayment ? GiftCardProductIdEnum::PLUS : GiftCardProductIdEnum::PRO,
            payment: $paymentDetails
        );
    }

    /**
     * Confirmationof wire from the blockchain.
     *
     * @param Wire $wire
     * @param Transaction $transaction - the transaction from the blockchain
     */
    public function confirm($wire, $transaction)
    {
        if ($wire->getSender()->guid != $transaction->getUserGuid()) {
            throw new \Exception('The user who requested this operation does not match the transaction');
        }

        if ($wire->getAmount() != $transaction->getData()['amount']) {
            throw new \Exception('The amount request does not match the transaction');
        }

        $wire->setGuid(Guid::build());
        $success = $this->repository->add($wire);

        //create a new transaction for receiver
        $data = $transaction->getData();
        $transaction
            ->setUserGuid($wire->getReceiver()->guid)
            ->setWalletAddress($data['receiver_address'])
            ->setAmount($wire->getAmount())
            ->setCompleted(true);
        $this->txRepo->add($transaction);

        $this->upgradesDelegate
            ->onWire($wire, $data['receiver_address']);

        $this->notificationDelegate->onAdd($wire);

        $this->cacheDelegate->onAdd($wire);

        return $success;
    }

    /**
     * Gets top channels sending/receiving offchain tokens for given timespan
     *
     * @param integer $from timestamp
     * @param integer $to timestamp
     * @param string $type - either 'actors' or 'beneficiaries'
     * @return array
     */

    public function getOffchainLeaderboard($from, $to, $type): array
    {
        $field = 'wire_sender_guid';

        if ($type !== 'actors') {
            $field = 'wire_receiver_guid';
        }

        /** @var Leaderboard $leaderboard */
        $leaderboard = Di::_()->get('Wire\Leaderboard');

        $result = $leaderboard->fetchOffchain($from, $to, $field);

        return $result;
    }

    /**
     * Whether receiver is plus handler.
     * @param string $receiverGuid
     * @return boolean whether receiver is plus handler.
     */
    public function isPlusReceiver(string $receiverGuid): bool
    {
        return $receiverGuid === (string) $this->config->get('plus')['handler'];
    }

    /**
     * Whether receiver is pro handler.
     * @param string $receiverGuid
     * @return boolean whether receiver is pro handler.
     */
    public function isProReceiver(string $receiverGuid): bool
    {
        return $receiverGuid === (string) $this->config->get('pro')['handler'];
    }

    /**
     * Get statement descriptor from Wire.
     * @param Wire $wire - wire to get payment descriptor for.
     * @return string payment descriptor.
     */
    public function getStatementDescriptorFromWire(Wire $wire): string
    {
        $receiverGuid = $wire->getReceiver()->getGuid();
        if ($this->isPlusReceiver($receiverGuid)) {
            return 'Plus sub';
        }
        if ($this->isProReceiver($receiverGuid)) {
            return 'Pro sub';
        }
        if ($this->supportTiersManager->getByWire($wire)) {
            return 'Membership';
        }
        return 'Tip';
    }

    /**
     * Get description from Wire.
     * @param Wire $wire - wire to get description for.
     * @return string description.
     */
    public function getDescriptionFromWire(Wire $wire): string
    {
        $receiverGuid = $wire->getReceiver()->getGuid();
        $receiverUsername = $wire->getReceiver()->getUsername();

        if ($this->isPlusReceiver($receiverGuid)) {
            return 'Minds Plus';
        }
        if ($this->isProReceiver($receiverGuid)) {
            return 'Minds Pro';
        }
        if ($supportTier = $this->supportTiersManager->getByWire($wire)) {
            return "@$receiverUsername's {$supportTier->getName()} Membership";
        }
        return "Tip to @$receiverUsername";
    }
}
