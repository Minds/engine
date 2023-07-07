<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\PaymentMethods;

use Exception;
use Minds\Core\Log\Logger;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Manager as GiftCardsManager;
use Minds\Core\Payments\Stripe\PaymentMethods\Manager as StripePaymentMethodsManager;
use Minds\Core\Payments\Stripe\PaymentMethods\PaymentMethod as StripePaymentMethod;
use Minds\Core\Payments\V2\Models\PaymentMethod;
use Minds\Entities\User;

class Manager
{
    public function __construct(
        private readonly StripePaymentMethodsManager $stripePaymentMethodsManager,
        private readonly GiftCardsManager $giftCardsManager,
        private readonly Logger $logger
    ) {
    }

    /**
     * @param User $user
     * @param GiftCardProductIdEnum|null $productIdEnum
     * @return array
     * @throws Exception
     */
    public function getPaymentMethods(
        User $user,
        ?GiftCardProductIdEnum $productIdEnum
    ): array {
        $paymentMethods = [];

        // Fetch gift cards
        if ($productIdEnum !== null) {
            $this->logger->info("Fetching gift cards for user {$user->getGuid()} and product $productIdEnum->name");
            $paymentMethods = array_merge($paymentMethods, $this->fetchGiftCardPaymentMethods($user, $productIdEnum));
        }

        // Fetch Stripe payment methods
        return array_merge($paymentMethods, $this->fetchStripePaymentMethods($user));
    }

    /**
     * @param User $user
     * @param GiftCardProductIdEnum $productIdEnum
     * @return PaymentMethod[]
     */
    private function fetchGiftCardPaymentMethods(
        User $user,
        GiftCardProductIdEnum $productIdEnum
    ): array {
        $giftCards = $this->giftCardsManager->getGiftCards(
            claimedByUser: $user,
            productId: $productIdEnum,
        );

        $totalGiftCardsBalance = 0;
        foreach ($giftCards as $giftCard) {
            $totalGiftCardsBalance += $giftCard->balance;
        }

        if ($totalGiftCardsBalance === 0) {
            return [];
        }

        return [
            new PaymentMethod(
                id: "gift_card",
                name: "Gift Card",
                balance: $totalGiftCardsBalance,
            )
        ];
    }

    /**
     * @param User $user
     * @return PaymentMethod[]
     * @throws Exception
     */
    private function fetchStripePaymentMethods(User $user): array
    {
        $stripePaymentMethods = $this->stripePaymentMethodsManager->getList([
            'limit' => 12,
            'user_guid' => $user->getGuid(),
        ]);

        $paymentMethods = [];

        /**
         * @var StripePaymentMethod $stripePaymentMethod
         */
        foreach ($stripePaymentMethods as $stripePaymentMethod) {
            $paymentMethods[] = new PaymentMethod(
                id: $stripePaymentMethod->getId(),
                name: $stripePaymentMethod->getCardBrand() . ' ***' . $stripePaymentMethod->getCardLast4() . ' - ' . $stripePaymentMethod->getCardExpires() . '',
                balance: null,
            );
        }
        return $paymentMethods;
    }
}
