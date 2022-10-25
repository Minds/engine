<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Payments;

use Exception;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Supermind\Settings\Manager as SettingsManager;
use Minds\Core\Supermind\Exceptions\SupermindRequestPaymentTypeNotFoundException;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;

class SupermindPaymentProcessor
{
    private User $user;

    /**
     * @const int Stripe service fee percentage
     */
    const SUPERMIND_SERVICE_FEE_PCT = 10;

    public function __construct(
        private ?IntentsManagerV2 $intentsManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?OffchainTransactions $offchainTransactions = null,
        private ?MindsConfig $mindsConfig = null,
        private ?SettingsManager $settingsManager = null
    ) {
        $this->intentsManager ??= new IntentsManagerV2();
        $this->mindsConfig ??= Di::_()->get("Config");
        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
        $this->offchainTransactions ??= new OffchainTransactions();#
        $this->settingsManager ??= Di::_()->get("Supermind\Settings\Manager");
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
        return $this;
    }

    /**
     * @param int $paymentMethod
     * @return float
     * @throws SupermindRequestPaymentTypeNotFoundException
     */
    public function getMinimumAllowedAmount(int $paymentMethod): float
    {
        $settings = $this->settingsManager->setUser($this->user)
            ->getSettings($paymentMethod);

        return match ($paymentMethod) {
            SupermindRequestPaymentMethod::CASH => $settings->getMinCash(),
            SupermindRequestPaymentMethod::OFFCHAIN_TOKEN => $settings->getMinOffchainTokens()
        };
    }

    /**
     * @param float $paymentAmount
     * @param int $paymentMethod
     * @return bool
     * @throws SupermindRequestPaymentTypeNotFoundException
     */
    public function isPaymentAmountAllowed(float $paymentAmount, int $paymentMethod): bool
    {
        return $paymentAmount >= $this->getMinimumAllowedAmount($paymentMethod);
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
            ->setServiceFeePct($this->mindsConfig->get('payments')['stripe']['service_fee_pct'] ?? self::SUPERMIND_SERVICE_FEE_PCT)
            ->setDescriptor('Supermind');
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
     * @throws OffchainWalletInsufficientFundsException
     * @throws Exception
     */
    public function setupOffchainPayment(SupermindRequest $request): string
    {
        $transaction = $this->offchainTransactions
            ->setUser(
                $this->buildUser($request->getSenderGuid())
            )
            ->setAmount((string) BigNumber::toPlain($request->getPaymentAmount(), 18)->neg())
            ->setType("supermind")
            ->setData([
                'supermind' => $request->getGuid(),
                'sender_guid' => $request->getSenderGuid(),
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
                $this->buildUser($request->getSenderGuid())
            )
            ->setAmount((string) BigNumber::toPlain($request->getPaymentAmount(), 18))
            ->setType("supermind")
            ->setData([
                'supermind' => $request->getGuid(),
                'sender_guid' => $request->getSenderGuid(),
                'receiver_guid' => $request->getReceiverGuid()
            ])
            ->create();
    }

    /**
     * @param SupermindRequest $request
     * @return bool
     * @throws LockFailedException
     * @throws Exception
     */
    public function creditOffchainPayment(SupermindRequest $request): bool
    {
        $this->offchainTransactions
            ->setUser(
                $this->buildUser($request->getReceiverGuid())
            )
            ->setAmount((string) BigNumber::toPlain($request->getPaymentAmount(), 18))
            ->setType("supermind")
            ->setData([
                'supermind' => $request->getGuid(),
                'sender_guid' => $request->getSenderGuid(),
                'receiver_guid' => $request->getReceiverGuid()
            ])
            ->create();
        return true;
    }
}
