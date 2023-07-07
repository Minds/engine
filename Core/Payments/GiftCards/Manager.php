<?php
namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Guid;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Delegates\EmailDelegate;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardPaymentTypeEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Exceptions\GiftCardAlreadyClaimedException;
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
    public function __construct(
        protected Repository $repository,
        protected PaymentsManager $paymentsManager,
        private readonly PaymentProcessor $paymentProcessor,
        private readonly EmailDelegate $emailDelegate,
        private readonly Logger $logger
    ) {
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

        // Build a guid out
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
                createdAt: time(),
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
    public function sendGiftCardToRecipient(GiftCardTarget $recipient, GiftCard $giftCard): void
    {
        $this->emailDelegate->onCreateGiftCard($giftCard, $recipient);
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
     * Allows the user to spend against their gift card
     */
    public function spend(
        User $user,
        GiftCardProductIdEnum $productId,
        PaymentDetails $payment,
    ): void {
        $uncollectedPaymentAmount = round($payment->paymentAmountMillis / 1000, 2) * -1;

        $giftCards = $this->repository->getGiftCards(
            claimedByGuid: $user->getGuid(),
            productId: $productId
        );

        foreach ($giftCards as $giftCard) {
            $uncollectedPaymentAmount -= $giftCard->getBalance();
            $this->repository->addGiftCardTransaction(
                new GiftCardTransaction(
                    paymentGuid: $payment->paymentGuid,
                    giftCardGuid: $giftCard->guid,
                    amount: $uncollectedPaymentAmount < 0 ? round($payment->paymentAmountMillis / 1000, 2) * -1 : $giftCard->balance * -1,
                    createdAt: time(),
                )
            );

            if ($uncollectedPaymentAmount <= 0) {
                return;
            }
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

        foreach ($transactions as $transaction) {
            $this->repository->addGiftCardTransaction(
                new GiftCardTransaction(
                    paymentGuid: $paymentGuid,
                    giftCardGuid: $transaction->giftCardGuid,
                    amount: $transaction->amount * -1, // Reverse the transaction
                    createdAt: time(),
                )
            );
        }
    }
}
