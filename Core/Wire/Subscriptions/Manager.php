<?php

namespace Minds\Core\Wire\Subscriptions;

use Minds\Core;
use Minds\Core\Di\Di;
use Minds\Core\Wire\Exceptions\WalletNotSetupException;
use Minds\Common\Urn;
use Minds\Entities;
use Minds\Entities\User;

class Manager
{
    /** @var Core\Wire\Manager */
    protected $wireManager;

    /** @var Core\Payments\Subscriptions\Manager $subscriptionsManager */
    protected $subscriptionsManager;

    /** @var Core\Payments\Subscriptions\Repository $subscriptionsRepository */
    protected $subscriptionsRepository;

    /** @var Config */
    protected $config;

    /** @var Core\Payments\Stripe\PaymentMethods\Manager */
    protected $stripePaymentMethodsManager;

    /** @var int $amount */
    protected $amount;

    /** @var User $sender */
    protected $sender;

    /** @var User $receiver */
    protected $receiver;

    /** @var string $method */
    protected $method = 'tokens';

    /** @var string $address */
    protected $address = 'offchain';

    public function __construct(
        $wireManager = null,
        $subscriptionsManager = null,
        $subscriptionsRepository = null,
        $config = null,
        $stripePaymentMethodsManager = null
    ) {
        $this->wireManager = $wireManager ?: Di::_()->get('Wire\Manager');
        $this->subscriptionsManager = $subscriptionsManager ?: Di::_()->get('Payments\Subscriptions\Manager');
        $this->subscriptionsRepository = $subscriptionsRepository ?: Di::_()->get('Payments\Subscriptions\Repository');
        $this->config = $config ?: Di::_()->get('Config');
        $this->stripePaymentMethodsManager = $stripePaymentMethodsManager ?? Di::_()->get('Stripe\PaymentMethods\Manager');
    }

    public function setAmount($amount): Manager
    {
        $this->amount = $amount;
        return $this;
    }

    public function setSender(User $sender): Manager
    {
        $this->sender = $sender;
        return $this;
    }

    public function setReceiver(User $receiver): Manager
    {
        $this->receiver = $receiver;
        return $this;
    }

    public function setAddress(string $address): Manager
    {
        $this->address = $address;
        return $this;
    }

    public function setMethod(string $method): Manager
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @return mixed
     * @throws WalletNotSetupException
     * @throws \Exception
     * @return string
     */
    public function create(): string
    {
        $this->cancelSubscription();

        $urn = "urn:subscription:" . implode('-', [
            $this->address, //offchain or onchain wallet
            $this->sender->getGuid(),
            $this->receiver->getGuid(),
        ]);

        $subscription = (new Core\Payments\Subscriptions\Subscription())
            ->setId($urn)
            ->setPlanId('wire')
            ->setPaymentMethod($this->method)
            ->setAmount($this->amount)
            ->setUser($this->sender)
            ->setEntity($this->receiver);

        $this->subscriptionsManager->setSubscription($subscription);
        $this->subscriptionsManager->create();

        return $subscription->getId();
    }

    /**
     * Call when a recurring wire is triggered.
     *
     * @param Core\Payments\Subscriptions\Subscription $subscription
     * @return bool
     */
    public function onRecurring($subscription): bool
    {
        $sender = new User($subscription->getUser()->guid);
        $receiver = new User($subscription->getEntity()->guid);
        $amount = $subscription->getAmount();

        $id = $subscription->getId();
        if (strpos($id, 'urn:', 0) !== 0) {
            error_log("[wire][recurring]: $id was expecting a urn");
            return false;
        }

        $urn = new Urn($id);
        list($address, , , ) = explode('-', $urn->getNss());

        switch ($address) {
            case "offchain":
                $this->wireManager->setPayload([
                    'method' => 'offchain',
                ]);
                break;
            case "stripe":
                $paymentMethods = $this->stripePaymentMethodsManager->getList(['user_guid' => $sender->getGuid()]);
                $paymentMethod = $paymentMethods[0]; // Todo: Remember the exact card
                if (!$paymentMethod) {
                    return false;
                }
                $this->wireManager->setPayload([
                    'method' => 'usd',
                    'paymentMethodId' => $paymentMethod->getId(),
                ]);
                break;
            default:
                // $txHash = $this->client->sendRawTransaction(
                //     $this->config->get('blockchain')['contracts']['wire']['wallet_pkey'],
                //     [
                //         'from' => $this->config->get('blockchain')['contracts']['wire']['wallet_address'],
                //         'to' => $this->config->get('blockchain')['contracts']['wire']['contract_address'],
                //         'gasLimit' => BigNumber::_(200000)->toHex(true),
                //         'data' => $this->client->encodeContractMethod('wireFromDelegate(address,address,uint256)', [
                //             $address,
                //             $receiver->getEthWallet(),
                //             BigNumber::_($this->token->toTokenUnit($amount))->toHex(true),
                //         ]),
                //     ]
                // );
                // $this->wireManager->setPayload([
                //     'method' => 'onchain',
                //     'address' => $address, //sender address
                //     'receiver' => $receiver->getEthWallet(),
                //     'txHash' => $txHash,
                // ]);
        }

        // Manager acts as factory
        $this->wireManager
            ->setSender($sender)
            ->setEntity($receiver)
            ->setAmount($subscription->getAmount());

        // Create the wire
        $this->wireManager->create();

        return true;
    }

    /**
     * Cancel a subscription
     * @return bool
     */
    protected function cancelSubscription(): bool
    {
        $subscriptions = $this->subscriptionsRepository->getList([
            'plan_id' => 'wire',
            'payment_method' => 'tokens',
            'entity_guid' => $this->receiver->guid,
            'user_guid' => $this->sender->guid
        ]);

        if (!$subscriptions) {
            return false;
        }

        $subscription = $subscriptions[0];

        $this->subscriptionsManager->setSubscription($subscription);

        // Cancel old subscription first
        $this->subscriptionsManager->cancel();

        return true;
    }
}
