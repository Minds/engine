<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Core\Blockchain\Wallets\OffChain\Exceptions\OffchainWalletInsufficientFundsException;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Boost\V3\Enums\BoostAdminAction;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Exceptions\BoostCashPaymentSetupFailedException;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Boost\V3\Onchain\AdminTransactionProcessor;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Core\Payments\V2\Enums\PaymentStatus;
use Minds\Core\Payments\V2\Exceptions\InvalidPaymentMethodException;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Util\BigNumber;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;

class PaymentProcessor
{
    public const SERVICE_FEE_PERCENT = 0;

    public function __construct(
        private ?IntentsManagerV2 $intentsManager = null,
        private ?EntitiesBuilder $entitiesBuilder = null,
        private ?OffchainTransactions $offchainTransactions = null,
        private ?MindsConfig $mindsConfig = null,
        private ?AdminTransactionProcessor $onchainAdminTransactionProcessor = null,
        private ?PaymentsManager $paymentsManager = null
    ) {
        $this->intentsManager ??= new IntentsManagerV2();
        $this->mindsConfig ??= Di::_()->get("Config");
        $this->entitiesBuilder ??= Di::_()->get("EntitiesBuilder");
        $this->offchainTransactions ??= new OffchainTransactions();
        $this->onchainAdminTransactionProcessor ??= new AdminTransactionProcessor();
        $this->paymentsManager ??= Di::_()->get(PaymentsManager::class);
    }

    /**
     * @param Boost $boost
     * @param User $user
     * @return bool
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     * @throws ServerErrorException
     * @throws InvalidPaymentMethodException
     * @throws Exception
     */
    public function setupBoostPayment(Boost $boost, User $user): bool
    {
        $result = match ($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->setupCashPaymentIntent($boost),
            BoostPaymentMethod::OFFCHAIN_TOKENS => $this->setupOffchainTokensPayment($boost),
            BoostPaymentMethod::ONCHAIN_TOKENS => throw new ServerErrorException("Onchain transactions are processed client-side"),
            default => throw new InvalidBoostPaymentMethodException()
        };
        $paymentDetails = $this->paymentsManager
            ->setUser($user)
            ->createPaymentFromBoost($boost);
        return $result;
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws Exception
     */
    private function setupCashPaymentIntent(Boost $boost): bool
    {
        try {
            $paymentIntent = (new PaymentIntent())
                ->setUserGuid($boost->getOwnerGuid())
                ->setAmount($boost->getPaymentAmount() * 100)
                ->setPaymentMethod($boost->getPaymentMethodId()) // Reference to the users card.
                ->setOffSession(true) // Needs to be off session.
                ->setConfirm(false) // Do not immediately confirm the transaction.
                ->setCaptureMethod('manual') // Hold funds rather than capturing immediately.
                ->setMetadata([
                    'boost_guid' => $boost->getGuid(),
                    'boost_sender_guid' => $boost->getOwnerGuid(),
                    'boost_owner_guid' => $boost->getOwnerGuid(),
                    'boost_entity_guid' => $boost->getEntityGuid(),
                    'boost_location' => BoostTargetLocation::toString($boost->getTargetLocation()),
                    'is_manual_transfer' => false // transfer method, NOT capture method.
                ])
                ->setServiceFeePct(self::SERVICE_FEE_PERCENT)
                ->setStatementDescriptor('Boost')
                ->setDescription($this->getCashPaymentStatementDescription($boost->getOwnerGuid()));

            $intent = $this->intentsManager->add($paymentIntent);

            $boost->setPaymentTxId($intent->getId());

            return true;
        } catch (Exception $e) {
            throw new BoostCashPaymentSetupFailedException();
        }
    }

    private function getCashPaymentStatementDescription(string $userGuid): string
    {
        /**
         * @var User $user
         */
        $user = $this->entitiesBuilder->single($userGuid);
        return "Boost from @{$user->getUsername()}";
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws OffchainWalletInsufficientFundsException
     */
    private function setupOffchainTokensPayment(Boost $boost): bool
    {
        $receiverGuid = $this->mindsConfig->get('boost')['offchain_wallet_guid'];

        /**
         * @var User $receiver
         */
        $receiver = $this->entitiesBuilder->single($receiverGuid);

        /**
         * @var User $sender
         */
        $sender = $this->entitiesBuilder->single($boost->getOwnerGuid());

        return $this->offchainTransactions
            ->setUser($receiver)
            ->setAmount((string) BigNumber::toPlain($boost->getPaymentAmount(), 18))
            ->setType('boost')
            ->setData([
                'boost' => $boost->getGuid(),
                'amount' => $boost->getPaymentAmount(),
                'sender_guid' => $boost->getOwnerGuid(),
                'receiver_guid' => $receiverGuid,
                'entity_guid' => $boost->getEntityGuid()
            ])
            ->transferFrom($sender);
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws ApiErrorException
     * @throws InvalidBoostPaymentMethodException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    public function captureBoostPayment(Boost $boost): bool
    {
        $result = match ($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->captureCashPaymentIntent($boost),
            BoostPaymentMethod::OFFCHAIN_TOKENS => $this->captureOffchainTokenPayment($boost),
            BoostPaymentMethod::ONCHAIN_TOKENS => $this->captureOnchainBoostPayment($boost),
            default => throw new InvalidBoostPaymentMethodException()
        };
        $this->paymentsManager->updatePaymentStatus($boost->getPaymentGuid(), PaymentStatus::COMPLETED, true);
        return $result;
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws StripeTransferFailedException
     * @throws ServerErrorException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    private function captureCashPaymentIntent(Boost $boost): bool
    {
        $boostOwner = $this->entitiesBuilder->single($boost->getOwnerGuid());
        if (!$boostOwner || !$boostOwner instanceof User) {
            $boostOwner = null;
        }
        return $this->intentsManager->capturePaymentIntent($boost->getPaymentTxId(), $boostOwner);
    }

    /**
     * @param Boost $boost
     * @return bool
     */
    private function captureOffchainTokenPayment(Boost $boost): bool
    {
        // Nothing to do here as the tokens are transferred at request time and refunded if boost request is rejected
        return true;
    }

    /**
     * Capture an onchain boost on the Ethereum network.
     * @param Boost $boost - onchain boost to capture.
     * @return bool true if boost has been captured.
     * @throws Exception - if an exception occurs.
     * @throws ServerErrorException - if there is an amount mismatch between blockchain and server.
     */
    private function captureOnchainBoostPayment(Boost $boost): bool
    {
        return (bool) $this->onchainAdminTransactionProcessor->send(
            boost: $boost,
            action: BoostAdminAction::ACCEPT
        );
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws ApiErrorException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws Exception
     */
    public function refundBoostPayment(Boost $boost): bool
    {
        $result = match ($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->refundCashPaymentIntent($boost),
            BoostPaymentMethod::OFFCHAIN_TOKENS => $this->refundOffchainTokensPayment($boost),
            BoostPaymentMethod::ONCHAIN_TOKENS => $this->refundOnchainTokensPayment($boost),
            default => throw new InvalidBoostPaymentMethodException()
        };
        $this->paymentsManager->updatePaymentStatus($boost->getPaymentGuid(), PaymentStatus::REFUNDED, false);
        return $result;
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws ApiErrorException
     */
    private function refundCashPaymentIntent(Boost $boost): bool
    {
        $boostOwner = $this->entitiesBuilder->single($boost->getOwnerGuid());
        if (!$boostOwner || !$boostOwner instanceof User) {
            $boostOwner = null;
        }
        return $this->intentsManager->cancelPaymentIntent($boost->getPaymentTxId(), $boostOwner);
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws KeyNotSetupException
     * @throws LockFailedException
     */
    private function refundOffchainTokensPayment(Boost $boost): bool
    {
        $senderGuid = $this->mindsConfig->get('boost')['offchain_wallet_guid'];

        /**
         * @var User $receiver
         */
        $receiver = $this->entitiesBuilder->single($boost->getOwnerGuid());

        /**
         * @var User $sender
         */
        $sender = $this->entitiesBuilder->single($senderGuid);

        return $this->offchainTransactions
            ->setUser($receiver)
            ->setAmount((string) BigNumber::toPlain($boost->getPaymentAmount(), 18))
            ->setType('boost')
            ->setData([
                'boost' => $boost->getGuid(),
                'amount' => $boost->getPaymentAmount(),
                'sender_guid' => $senderGuid,
                'receiver_guid' => $boost->getOwnerGuid(),
                'entity_guid' => $boost->getEntityGuid()
            ])
            ->transferFrom($sender);
    }

    /**
     * Refund an onchain boost on the Ethereum network by calling
     * the contracts reject function.
     * @param Boost $boost - onchain boost to refund.
     * @return bool true if boost has been refunded.
     * @throws Exception - if an exception occurs.
     */
    private function refundOnchainTokensPayment(Boost $boost): bool
    {
        return (bool) $this->onchainAdminTransactionProcessor->send(
            boost: $boost,
            action: BoostAdminAction::REJECT,
        );
    }
}
