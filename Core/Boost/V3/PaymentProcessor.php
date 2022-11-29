<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Exception;
use Minds\Core\Blockchain\Wallets\OffChain\Transactions as OffchainTransactions;
use Minds\Core\Boost\V3\Enums\BoostPaymentMethod;
use Minds\Core\Boost\V3\Enums\BoostTargetLocation;
use Minds\Core\Boost\V3\Exceptions\InvalidBoostPaymentMethodException;
use Minds\Core\Boost\V3\Models\Boost;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Data\Locks\KeyNotSetupException;
use Minds\Core\Data\Locks\LockFailedException;
use Minds\Core\Di\Di;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\Stripe\Intents\ManagerV2 as IntentsManagerV2;
use Minds\Core\Payments\Stripe\Intents\PaymentIntent;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use NotImplementedException;
use Stripe\Exception\ApiErrorException;

class PaymentProcessor
{
    public const SERVICE_FEE_PERCENT = 0;

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
     * @param Boost $boost
     * @return bool
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     * @throws Exception
     */
    public function setupBoostPayment(Boost $boost): bool
    {
        return match ($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->setupCashPaymentIntent($boost),
            BoostPaymentMethod::OFFCHAIN_TOKENS => $this->setupOffchainTokensPayment($boost),
            BoostPaymentMethod::ONCHAIN_TOKENS => throw new NotImplementedException("The payment method selected is not available yet"),
            default => throw new InvalidBoostPaymentMethodException()
        };
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws Exception
     */
    private function setupCashPaymentIntent(Boost $boost): bool
    {
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
            ->setAmount($boost->getPaymentAmount())
            ->setType('boost')
            ->setData([
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
     * @throws NotImplementedException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    public function captureBoostPayment(Boost $boost): bool
    {
        return match ($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->captureCashPaymentIntent($boost),
            BoostPaymentMethod::OFFCHAIN_TOKENS => $this->captureOffchainTokenPayment($boost),
            BoostPaymentMethod::ONCHAIN_TOKENS => throw new NotImplementedException("The payment method selected is not available yet"),
            default => throw new InvalidBoostPaymentMethodException()
        };
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
        return $this->intentsManager->capturePaymentIntent($boost->getPaymentTxId());
    }

    private function captureOffchainTokenPayment(Boost $boost): bool
    {
        // Nothing to do here as the tokens are transferred at request time and refunded if boost request is rejected
        return true;
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws ApiErrorException
     * @throws InvalidBoostPaymentMethodException
     * @throws KeyNotSetupException
     * @throws LockFailedException
     * @throws NotImplementedException
     */
    public function refundBoostPayment(Boost $boost): bool
    {
        return match ($boost->getPaymentMethod()) {
            BoostPaymentMethod::CASH => $this->refundCashPaymentIntent($boost),
            BoostPaymentMethod::OFFCHAIN_TOKENS => $this->refundOffchainTokensPayment($boost),
            BoostPaymentMethod::ONCHAIN_TOKENS => throw new NotImplementedException("The payment method selected is not available yet"),
            default => throw new InvalidBoostPaymentMethodException()
        };
    }

    /**
     * @param Boost $boost
     * @return bool
     * @throws ApiErrorException
     */
    private function refundCashPaymentIntent(Boost $boost): bool
    {
        return $this->intentsManager->cancelPaymentIntent($boost->getPaymentTxId());
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
            ->setAmount($boost->getPaymentAmount())
            ->setType('boost')
            ->setData([
                'amount' => $boost->getPaymentAmount(),
                'sender_guid' => $senderGuid,
                'receiver_guid' => $boost->getOwnerGuid(),
                'entity_guid' => $boost->getEntityGuid()
            ])
            ->transferFrom($sender);
    }
}
