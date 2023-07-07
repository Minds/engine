<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Payments;

use Exception;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Supermind\Models\SupermindRequest;
use Minds\Core\Supermind\Settings\Manager as SettingsManager;
use Minds\Core\Supermind\SupermindRequestPaymentMethod;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Minds\Exceptions\UserNotFoundException;
use Stripe\Exception\ApiErrorException;

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

        $intent = $this->getIntentsManager()->add($paymentIntent);

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

        $paymentIntent = (new PaymentIntent())
            ->setUserGuid($request->getSenderGuid())
            ->setAmount($request->getPaymentAmount() * 100)
            ->setPaymentMethod($paymentMethodId)
            ->setOffSession(true)
            ->setConfirm(false)
            ->setCaptureMethod('manual')
            ->setMetadata([
                'supermind' => $request->getGuid(),
                'receiver_guid' => $request->getReceiverGuid(),
                'user_guid' => $request->getSenderGuid(),
            ])
            ->setServiceFeePct($this->mindsConfig->get('payments')['stripe']['service_fee_pct'] ?? self::SUPERMIND_SERVICE_FEE_PCT)
            ->setStatementDescriptor($this->getStatementDescriptor())
            ->setDescription($this->getDescription($receiver));

        if ($receiver->getMerchant()['id']) {
            $paymentIntent->setStripeAccountId($receiver->getMerchant()['id']);
        } else {
            $paymentIntent->setStripeFutureAccountGuid($receiver->getGuid());
        }

        return $paymentIntent;
    }

    /**
     * Build user from user guid.
     * @param string $guid - guid of the user.
     * @return User|null user if one is found.
     */
    private function buildUser(string $guid): ?User
    {
        $user = $this->entitiesBuilder->single($guid);
        return $user instanceof User ? $user : null;
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws StripeTransferFailedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    public function capturePaymentIntent(string $paymentIntentId): bool
    {
        return $this->getIntentsManager()->capturePaymentIntent($paymentIntentId);
    }

    /**
     * @param string $paymentIntentId
     * @return bool
     * @throws \Stripe\Exception\ApiErrorException
     */
    public function cancelPaymentIntent(string $paymentIntentId): bool
    {
        return $this->getIntentsManager()->cancelPaymentIntent($paymentIntentId);
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
     * @return string
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws KeyNotSetupException
     */
    public function refundOffchainPayment(SupermindRequest $request): string
    {
        $user = $this->buildUser($request->getSenderGuid());

        if (!$user) {
            throw new UserNotFoundException(
                "User ({$request->getSenderGuid()}) not found for Supermind with guid: {$request->getGuid()}"
            );
        }

        $transaction = $this->offchainTransactions
            ->setUser($user)
            ->setAmount((string) BigNumber::toPlain($request->getPaymentAmount(), 18))
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

    /**
     * Get statement descriptor for PaymentIntent.
     * @return string statement descriptor.
     */
    public function getStatementDescriptor(): string
    {
        return 'Supermind';
    }

    /**
     * Get description for PaymentIntent.
     * @return string description.
     */
    public function getDescription(User $receiver): string
    {
        return "Supermind to @{$receiver->getUsername()}";
    }

    /**
     * We provide the dependency via this function as in the contructor it is very slow, futher
     * down the tree it attempts to decrypt emails for Stripe dev mode
     */
    private function getIntentsManager()
    {
        if (!$this->intentsManager) {
            $this->intentsManager = new IntentsManagerV2();
        }
        return $this->intentsManager;
    }
}
