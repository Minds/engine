<?php
/**
 * Created by Marcelo.
 * Date: 03/07/2017.
 */

namespace Minds\Core\Wire;

use Minds\Core;
use Minds\Core\Data;
use Minds\Core\Di\Di;
use Minds\Core\Events\Dispatcher;
use Minds\Core\Guid;
use Minds\Core\Util\BigNumber;
use Minds\Core\Wire\Exceptions\WalletNotSetupException;
use Minds\Core\Wire\Subscriptions\Manager as SubscriptionsManager;
use Minds\Common\Urn;
use Minds\Entities;
use Minds\Entities\User;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Payments\Stripe\Intents\Manager as StripeIntentsManager;

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

    /** @var int */
    const WIRE_SERVICE_FEE_PCT = 5;

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
        $acl = null
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

        $wire = new Wire();
        $wire
            ->setSender($this->sender)
            ->setReceiver($this->receiver)
            ->setEntity($this->entity)
            ->setAmount($this->amount)
            ->setTimestamp(time())
            ->setRecurringInterval($this->recurringInterval);

        // If Minds+ is the reciever, bypass the ACL
        $bypassAcl = false;
        if ($this->receiver->getGuid() !== $this->config->get('plus')['handler']) {
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

                if (!$this->cap->isAllowed($this->amount)) {
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

                // Determine if a trial is eligible
                // If the reciever is Minds+ channel and the sender has never has plus (no plus_expires field)
                // then they will have a trial.
                if ($this->receiver->getGuid() == $this->config->get('plus')['handler'] && !$this->sender->getPlusExpires()) {
                    $wire->setTrialDays(7);
                }

                // If this is a trial, we still create the subscription but do not charge
                if (!$wire->getTrialDays()) {
                    $intent = new PaymentIntent();
                    $intent
                        ->setUserGuid($this->sender->getGuid())
                        ->setAmount($this->amount)
                        ->setPaymentMethod($this->payload['paymentMethodId'])
                        ->setOffSession(true)
                        ->setConfirm(true)
                        ->setStripeAccountId($this->receiver->getMerchant()['id'])
                        ->setServiceFeePct(static::WIRE_SERVICE_FEE_PCT);

                    // Charge stripe
                    $this->stripeIntentsManager->add($intent);
                }

                $wire->setAddress('stripe')
                    ->setMethod('usd');

                // Save the wire to the Repository
                $this->repository->add($wire);

                // Notify plus/pro
                $this->upgradesDelegate
                    ->onWire($wire, 'usd');

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
}
