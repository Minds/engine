<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Payments;

use Exception;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Entities\User;

class SupermindPaymentProcessor
{
    const SUPERMIND_SERVICE_FEE_PCT = 10;

    public function __construct(
        private ?IntentsManagerV2 $intentsManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?OffchainTransactions $offchainTransactions = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->intentsManager ??= new IntentsManagerV2();
        $this->mindsConfig ??= Di::_()->get("Config");
        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
        $this->offchainTransactions ??= new OffchainTransactions();
    }

    /**
     * @param string $paymentMethodId
     * @param SupermindRequest $request
     * @return string
     * @throws Exception
     */
    public function setupSupermindStripePayment(string $paymentMethodId, SupermindRequest $request): string
    {
        $paymentIntent = $this->preparePaymentIntent($paymentMethodId, $request);

        $intent = $this->intentsManager->add($paymentIntent);

        return $intent->getId();
    }

    /**
     * @param string $paymentMethodId
     * @param SupermindRequest $request
     * @return PaymentIntent
     */
    private function preparePaymentIntent(string $paymentMethodId, SupermindRequest $request): PaymentIntent
    {
        $receiver = $this->buildUser($request->getReceiverGuid());

        return (new PaymentIntent())
            ->setUserGuid($request->getSenderGuid())
            ->setAmount($request->getPaymentAmount() * 100)
            ->setPaymentMethod($paymentMethodId)
            ->setOffSession(true)
            ->setConfirm(false)
            ->setCaptureMethod('manual')
            ->setStripeAccountId($receiver->getMerchant()['id'])
            ->setMetadata([
                'supermind' => $request->getGuid(),
                'receiver_guid' => $request->getReceiverGuid(),
                'user_guid' => $request->getSenderGuid(),
            ])
            ->setServiceFeePct($this->mindsConfig->get('payments')['stripe']['service_fee_pct'] ?? self::SUPERMIND_SERVICE_FEE_PCT);
    }

    private function buildUser(string $userGuid): User
    {
        return $this->entitiesBuilder->single($userGuid);
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function capturePaymentIntent(string $paymentIntentId): bool
    {
        return $this->intentsManager->capturePaymentIntent($paymentIntentId);
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId): bool
    {
        return $this->intentsManager->cancelPaymentIntent($paymentIntentId);
    }

    /**
     * @param SupermindRequest $request
     * @return string
     * @throws LockFailedException
     * @throws Exception
     */
    public function setupOffchainPayment(SupermindRequest $request): string
    {
        $transaction = $this->offchainTransactions
            ->setUser(
                $this->buildUser($request->getSenderGuid())
            )
            ->setAmount(-$request->getPaymentAmount())
            ->setType("supermind")
            ->setData([
                'supermind' => $request->getGuid(),
                'user_guid' => $request->getSenderGuid(),
                'receiver_guid' => $request->getReceiverGuid()
            ])
            ->create();

        return $transaction->getTx();
    }

    /**
     * @param SupermindRequest $request
     * @return void
     * @throws LockFailedException
     * @throws Exception
     */
    public function refundOffchainPayment(SupermindRequest $request): void
    {
        $this->offchainTransactions
            ->setUser(
                $this->retrieveUser($request->getSenderGuid())
            )
            ->setAmount($request->getPaymentAmount())
            ->setType("supermind")
            ->setData([
                'supermind' => $request->getGuid(),
                'user_guid' => $request->getSenderGuid(),
                'receiver_guid' => $request->getReceiverGuid()
            ])
            ->create();
    }

    /**
     * @param SupermindRequest $request
     * @return bool
     * @throws LockFailedException
     */
    public function creditOffchainPayment(SupermindRequest $request): bool
    {
        $this->offchainTransactions
            ->setUser(
                $this->buildUser($request->getReceiverGuid())
            )
            ->setAmount($request->getPaymentAmount())
            ->setType("supermind")
            ->setData([
                'supermind' => $request->getGuid(),
                'user_guid' => $request->getSenderGuid(),
                'receiver_guid' => $request->getReceiverGuid()
            ])
            ->create();
        return true;
    }
}
