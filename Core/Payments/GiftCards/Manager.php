<?php
namespace Minds\Core\Payments\GiftCards;

use Minds\Core\Guid;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Core\Payments\GiftCards\Models\GiftCardTransaction;
use Minds\Core\Payments\V2\Models\PaymentDetails;
use Minds\Core\Payments\V2\Manager as PaymentsManager;
use Minds\Entities\User;

class Manager
{
    public function __construct(
        protected Repository $repository,
        protected PaymentsManager $paymentsManager
    ) {
    }

    /**
     * Creates a gift card and authorizes payment
     */
    public function createGiftCard(
        User $issuer,
        GiftCardProductIdEnum $productId,
        float $amount,
        ?int $expiresAt = null
    ): GiftCard {
        // If no expiry time set, we will expire in 1 year
        if (!$expiresAt) {
            $expiresAt = strtotime('+1 year');
        }

        // Build a guid out
        $guid = Guid::build();

        // TODO: payment logic
        $paymentDetails = new PaymentDetails([
            'paymentAmountMillis' => (int) round($amount * 1000),
            'userGuid' => (int) $issuer->getGuid(),
            'paymentType' => 0,
            'paymentMethod' => 0,
        ]);
        $this->paymentsManager->createPayment($paymentDetails);

        // Construct the gift card
        $giftCard = new GiftCard(
            guid: $guid,
            productId: $productId,
            amount: $amount,
            issuedByGuid: $issuer->getGuid(),
            issuedAt: time(),
            claimCode: 'todo',
            expiresAt: $expiresAt,
        );

        // Open a transaction
        $this->repository->beginTransaction();

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

        // Commit the transaction
        $this->repository->commitTransaction();

        return $giftCard;
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
     */
    public function claimGiftCard(
        GiftCard $giftCard,
        User $claimant,
        string $claimCode,
    ): bool {
        // Check its not already been claimed
        if ($giftCard->isClaimed()) {
            throw new \Exception("This giftcard has already been claimed");
        }

        // Verify the claim code
        if ($giftCard->claimCode !== $claimCode) {
            throw new \Exception("Invalid claim code");
        }

        $giftCard
            ->setClaimedByGuid($claimant->getGuid())
            ->setClaimedAt(time());

        return $this->repository->updateGiftCardClaim($giftCard);
    }

    // public function getAllGiftCards(User $user): iterable

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
    public function getUserTransactions(
        User $user,
        int $limit = Repository::DEFAULT_LIMIT,
        string &$loadAfter = null,
        string &$loadBefore = null,
        ?bool &$hasMore = false
    ): iterable
    {
        return $this->repository->getUserTransactions(
            giftCardCalimedByUserGuid: $user->getGuid(),
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
    ) {
        // Collect the balances of available gift cards

        // Find the oldest gift card, deduct the remainder from $amount
        $giftCards = iterator_to_array($this->repository->getGiftCards(
            claimedByGuid: $user->getGuid(),
            productId: $productId,
            ordering: GiftCardOrderingEnum::CREATED_ASC
        ));
  
        if (empty($giftCards)) {
            throw new \Exception("You dont have any valid gift cards");
        }

        // Psuedo code for testing
        // Create a transaction and debit
        $giftCardTransaction = new GiftCardTransaction(
            paymentGuid: $payment->paymentGuid,
            giftCardGuid: $giftCards[0]->guid,
            amount: round($payment->paymentAmountMillis / 1000, 2) * -1,
            createdAt: time(),
        );

        $this->repository->addGiftCardTransaction($giftCardTransaction);
    }
}
