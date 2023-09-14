<?php
namespace Minds\Core\Payments\GiftCards;

use Minds\Common\SystemUser;
use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Delegates\EmailDelegate;
use Minds\Core\Payments\GiftCards\Delegates\NotificationDelegate;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardPaymentTypeEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardStatusFilterEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardAlreadyClaimedException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardInsufficientFundsException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardNotFoundException;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardPaymentFailedException;
use Minds\Core\Payments\GiftCards\Exceptions\InvalidGiftCardClaimCodeException;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use Minds\Core\Payments\GiftCards\Types\GiftCardTarget;
use Minds\Core\Payments\Stripe\Exceptions\StripeTransferFailedException;
use Minds\Core\Payments\V2\Enums\PaymentMethod;
use Minds\Core\Payments\V2\Enums\PaymentType;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Exceptions\UserErrorException;
use Stripe\Exception\ApiErrorException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class Manager
{
    private bool $inTransaction = false;

    public function __construct(
        protected Repository $repository,
        protected PaymentsManager $paymentsManager,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly EmailDelegate $emailDelegate,
        private readonly Logger $logger,
        private readonly NotificationDelegate $notificationDelegate,
    ) {
    }

    public function setInTransaction(bool $value): void
    {
        $this->inTransaction = $value;
    }

    /**
     * Returns true if we are currently in a transaction
     * @return bool
     */
    public function isInTransaction(): bool
    {
        return $this->inTransaction;
    }

    public function commitTransaction(): void
    {
        if ($this->inTransaction) {
            $this->repository->commitTransaction();
        }
    }

    public function rollbackTransaction(): void
    {
        if ($this->inTransaction) {
            $this->repository->rollbackTransaction();
        }
    }

    /**
     * @param User $issuer
     * @param GiftCardProductIdEnum $productId
     * @param float $amount
     * @param string $stripePaymentMethodId
     * @param int|null $expiresAt
     * @param GiftCardPaymentTypeEnum $giftCardPaymentTypeEnum
     * @return GiftCard
     * @throws GiftCardPaymentFailedException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     * @throws ApiErrorException
     */
    public function createGiftCard(
        User $issuer,
        GiftCardProductIdEnum $productId,
        float $amount,
        string $stripePaymentMethodId,
        ?int $expiresAt = null,
        GiftCardPaymentTypeEnum $giftCardPaymentTypeEnum = GiftCardPaymentTypeEnum::CASH
    ): GiftCard {
        // If no expiry time set, we will expire in 1 year
        if (!$expiresAt) {
            $expiresAt = strtotime('+1 year');
        }

        // Build a guid
        $giftCardGuid = Guid::build();

        $issuedAt = time();

        // Construct the gift card
        $giftCard = new GiftCard(
            guid: $giftCardGuid,
            productId: $productId,
            amount: $amount,
            issuedByGuid: $issuer->getGuid(),
            issuedAt: $issuedAt,
            claimCode: $this->generateClaimCode($giftCardGuid, $issuer->getGuid(), $productId, $issuedAt, $amount),
            expiresAt: $expiresAt,
        );

        try {
            $paymentRef = "internal";
            if ($giftCardPaymentTypeEnum === GiftCardPaymentTypeEnum::CASH) {
                $paymentRef = $this->paymentProcessor->setupPayment($giftCard, $stripePaymentMethodId);
            }
            // Open a transaction
            $this->repository->beginTransaction();

            $paymentDetails = new PaymentDetails([
                'paymentAmountMillis' => (int) round($amount * 1000),
                'userGuid' => (int) $issuer->getGuid(),
                'paymentType' => PaymentType::GIFT_CARD_PURCHASE,
                'paymentMethod' => PaymentMethod::GIFT_CARD,
                'paymentTxId' => $paymentRef

            ]);
            $this->paymentsManager->createPayment($paymentDetails);

            // Save to the database
            $this->repository->addGiftCard($giftCard);

            // Create the initial deposit on to the gift card
            $giftCardTransaction = new GiftCardTransaction(
                paymentGuid: $paymentDetails->paymentGuid,
                giftCardGuid: $giftCard->guid,
                amount: $amount,
                createdAt: time()
            );
            $this->repository->addGiftCardTransaction($giftCardTransaction);

            if ($giftCardPaymentTypeEnum === GiftCardPaymentTypeEnum::CASH) {
                $this->paymentProcessor->capturePayment($paymentRef, $issuer);
            }

            // Commit the transaction
            $this->repository->commitTransaction();

            return $giftCard;
        } catch (GiftCardPaymentFailedException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->repository->rollbackTransaction();
            throw $e;
        }
    }

    /**
     * @param GiftCardTarget $recipient
     * @param GiftCard $giftCard
     * @return void
     * @throws GraphQLException
     */
    public function sendGiftCardToRecipient(User $sender, GiftCardTarget $recipient, GiftCard $giftCard): void
    {
        $this->emailDelegate->onCreateGiftCard($giftCard, $recipient, $sender);
        $this->notificationDelegate->onCreateGiftCard($giftCard, $recipient);
    }

    private function generateClaimCode(
        int $guid,
        int $issuerGuid,
        GiftCardProductIdEnum $productIdEnum,
        int $issuedAt,
        float $amount
    ): string {
        return hash("sha256", $guid . $issuerGuid . $productIdEnum->value . $issuedAt . $amount);
    }

    /**
     * Returns multiple gift cards
     * @param User $claimedByUser
     * @param User|null $issuedByUser
     * @param GiftCardProductIdEnum|null $productId
     * @param int $limit
     * @param GiftCardOrderingEnum $ordering
     * @param string|null $loadAfter
     * @param string|null $loadBefore
     * @param bool|null $hasMore
     * @return iterable<GiftCard>
     */
    public function getGiftCards(
        User $claimedByUser,
        ?User $issuedByUser = null,
        ?GiftCardProductIdEnum $productId = null,
        ?GiftCardStatusFilterEnum $statusFilter = null,
        int $limit = Repository::DEFAULT_LIMIT,
        GiftCardOrderingEnum $ordering = GiftCardOrderingEnum::CREATED_ASC,
        ?string &$loadAfter = null,
        ?string &$loadBefore = null,
        ?bool &$hasMore = null
    ): iterable {
        return $this->repository->getGiftCards(
            claimedByGuid: $claimedByUser->getGuid(),
            issuedByGuid: $issuedByUser?->getGuid(),
            productId: $productId,
            statusFilter: $statusFilter,
            limit: $limit,
            ordering: $ordering,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );
    }

    /**
     * Returns a single GiftCard
     */
    public function getGiftCard(int $guid): GiftCard
    {
        return $this->repository->getGiftCard($guid);
    }

    /**
     * Returns a single GiftCard by its claim code.
     * @return GiftCard - gift card by claim code.
     */
    public function getGiftCardByClaimCode(string $claimCode): GiftCard
    {
        return $this->repository->getGiftCardByClaimCode($claimCode);
    }

    /**
     * A user can claim a gift code if they know the claim code
     * @param User $claimant
     * @param string $claimCode
     * @return GiftCard
     * @throws GiftCardAlreadyClaimedException
     * @throws GiftCardNotFoundException
     * @throws InvalidGiftCardClaimCodeException
     * @throws ServerErrorException
     */
    public function claimGiftCard(
        User $claimant,
        string $claimCode,
    ): GiftCard {
        $giftCard = $this->repository->getGiftCardByClaimCode($claimCode);
        // Check it's not already been claimed
        if ($giftCard->isClaimed()) {
            throw new GiftCardAlreadyClaimedException();
        }

        // Verify the claim code
        if ($giftCard->claimCode !== $claimCode) {
            throw new InvalidGiftCardClaimCodeException();
        }

        $giftCard
            ->setClaimedByGuid($claimant->getGuid())
            ->setClaimedAt(time());

        // TODO: Add transaction record for the claim?

        $this->repository->updateGiftCardClaim($giftCard);

        return $giftCard;
    }

    /**
     * Returns the users remaining balance across all gift cards
     * @return float
     */
    public function getUserBalance(User $user): float
    {
        return $this->repository->getUserBalance($user->getGuid());
    }

    /**
     * Returns the users remaining balance across all gift cards, returned by the product id
     * @return float[]
     */
    public function getUserBalanceByProduct(User $user): array
    {
        return $this->repository->getUserBalanceByProduct($user->getGuid());
    }

    /**
     * @param User $user
     * @param GiftCardProductIdEnum $productIdEnum
     * @return float
     * @throws GiftCardNotFoundException
     * @throws ServerErrorException
     */
    public function getUserBalanceForProduct(User $user, GiftCardProductIdEnum $productIdEnum): float
    {
        return $this->repository->getUserBalanceForProduct($user->getGuid(), $productIdEnum);
    }

    /**
     * Returns transactions associated with a user
     * @return iterable<GiftCardTransaction>
     */
    public function getGiftCardTransactions(
        User $user,
        ?GiftCard $giftCard = null,
        int $limit = Repository::DEFAULT_LIMIT,
        string &$loadAfter = null,
        string &$loadBefore = null,
        ?bool &$hasMore = false
    ): iterable {
        return $this->repository->getGiftCardTransactions(
            giftCardClaimedByUserGuid: $user->getGuid(),
            giftCardGuid: $giftCard?->guid,
            limit: $limit,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );
    }

    /**
     * Returns transactions associated with a user with additional data
     * for display in a ledger, such as Boost guids.
     * @param int $giftCardGuid - guid of the gift card to get transactions for.
     * @param int $limit - limit of transactions to return.
     * @param string &$loadAfter - cursor to load after.
     * @param string &$loadBefore - cursor to load before.
     * @param ?bool &$hasMore - whether there are more transactions to load.
     * @return iterable<GiftCardTransaction>
     */
    public function getGiftCardTransactionLedger(
        User $user,
        int $giftCardGuid,
        int $limit = Repository::DEFAULT_LIMIT,
        string &$loadAfter = null,
        string &$loadBefore = null,
        ?bool &$hasMore = false
    ): iterable {
        return $this->repository->getGiftCardTransactionLedger(
            giftCardClaimedByUserGuid: $user->getGuid(),
            giftCardGuid: $giftCardGuid,
            limit: $limit,
            loadAfter: $loadAfter,
            loadBefore: $loadBefore,
            hasMore: $hasMore,
        );
    }

    /**
     * Allows the user to spend against their gift card
     * @param User $user
     * @param GiftCardProductIdEnum $productId
     * @param PaymentDetails $payment
     * @throws GiftCardInsufficientFundsException
     * @throws GiftCardNotFoundException
     * @throws ServerErrorException
     */
    public function spend(
        User $user,
        GiftCardProductIdEnum $productId,
        PaymentDetails $payment,
    ): void {
        $uncollectedPaymentAmount = round($payment->paymentAmountMillis / 1000, 2);

        $totalGiftCardBalance = $this->repository->getUserBalanceForProduct((int) $user->getGuid(), $productId);

        if ($totalGiftCardBalance < $uncollectedPaymentAmount) {
            throw new GiftCardInsufficientFundsException();
        }

        $giftCards = $this->repository->getGiftCards(
            claimedByGuid: $user->getGuid(),
            productId: $productId,
            statusFilter: GiftCardStatusFilterEnum::ACTIVE,
        );

        $createdAtTimestamp = time();

        $this->repository->beginTransaction();

        $paymentSuccessful = false;
        foreach ($giftCards as $giftCard) {
            if ($giftCard->balance <= 0) {
                continue;
            }

            $uncollectedPaymentAmount -= $giftCard->balance;
            if (
                !$this->repository->addGiftCardTransaction(
                    new GiftCardTransaction(
                        paymentGuid: $payment->paymentGuid,
                        giftCardGuid: $giftCard->guid,
                        amount: $uncollectedPaymentAmount < 0 ? ($uncollectedPaymentAmount + $giftCard->balance) * -1 : $giftCard->balance * -1,
                        createdAt: $createdAtTimestamp
                    )
                )
            ) {
                $this->repository->rollbackTransaction();
                throw new ServerErrorException();
            }

            if ($uncollectedPaymentAmount <= 0) {
                $paymentSuccessful = true;
                break;
            }
        }

        if (!$paymentSuccessful) {
            $this->repository->rollbackTransaction();
            throw new GiftCardInsufficientFundsException();
        }

        if (!$this->inTransaction) {
            $this->repository->commitTransaction();
        }
    }

    /**
     * @param int $paymentGuid
     * @return void
     * @throws ServerErrorException
     */
    public function refund(int $paymentGuid): void
    {
        $transactions = $this->repository->getGiftCardTransactionsFromPaymentGuid($paymentGuid);

        $this->logger->info('Refunding gift card transactions', [
            'paymentGuid' => $paymentGuid,
            'transactions' => $transactions,
        ]);

        $refundedAt = time();

        foreach ($transactions as $transaction) {
            $this->repository->markTransactionAsRefunded(
                paymentGuid: $paymentGuid,
                giftCardGuid: $transaction->giftCardGuid,
                refundedAt: $refundedAt
            );
        }
    }

    /**
     * Issues gift cards to a Minds Plus or Minds Pro subscriber and notify the recipient
     * @param User $recipient
     * @param float $amount
     * @param int $expiryTimestamp
     * @return void
     * @throws ApiErrorException
     * @throws GiftCardPaymentFailedException
     * @throws GraphQLException
     * @throws ServerErrorException
     * @throws StripeTransferFailedException
     * @throws UserErrorException
     */
    public function issueMindsPlusAndProGiftCards(?User $sender, User $recipient, float $amount, int $expiryTimestamp): void
    {
        $this->logger->info("Issuing gift cards to " . $recipient->getGuid() . " for $" . $amount . " (expires " . date("Y-m-d H:i:s", $expiryTimestamp) . ")");

        foreach (GiftCardProductIdEnum::enabledGiftsForPlusPro() as $productIdEnum) {
            /**
             * Issuing a new gift card for the enabled gift card's product
             */
            $creditsGiftCard = $this->createGiftCard(
                issuer: new SystemUser(),
                productId: $productIdEnum,
                amount: $amount,
                stripePaymentMethodId: "",
                expiresAt: $expiryTimestamp,
                giftCardPaymentTypeEnum: GiftCardPaymentTypeEnum::INTERNAL
            );

            /**
             * Send email and push notifications to recipient for gift card to be claimed
             */
            $this->sendGiftCardToRecipient(
                sender: $sender ?? new SystemUser(),
                recipient: new GiftCardTarget(
                    targetUserGuid: (int) $recipient->getGuid()
                ),
                giftCard: $creditsGiftCard
            );

            $this->logger->info("Issued gift card " . $creditsGiftCard->guid . " to " . $recipient->getGuid() . " for $" . $amount . " with product $productIdEnum->name (expires " . date("Y-m-d H:i:s", $expiryTimestamp) . ")");
        }
    }
}
