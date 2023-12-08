<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Services;

use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Core\Payments\Stripe\Checkout\Enums\CheckoutModeEnum;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService;
use Minds\Entities\User;
use Psr\SimpleCache\CacheInterface;

class CheckoutService
{
    private const CACHE_TTL = 3600;

    public function __construct(
        private readonly StripeCheckoutManager $stripeCheckoutManager,
        private readonly ProductPriceService $productPriceService,
        private readonly CacheInterface $cache,
    ) {
    }

    public function generateCheckoutLink(
        User $user,
        string $planId,
        CheckoutTimePeriodEnum $timePeriod,
        ?array $addOnIds
    ): string {
        $planStripePrice = $this->productPriceService->getPriceDetails(
            user: $user,
            lookUpKey: "product__network__community"
        );

        $checkoutSession = $this->stripeCheckoutManager->createSession(
            user: $user,
            mode: CheckoutModeEnum::SUBSCRIPTION,
            successUrl: "http://localhost:4200/api/v3/payments/checkout/complete",
            lineItems: [
                [
                    'price' => $planStripePrice->id,
                    'quantity' => 1,
                ]
            ],
            paymentMethodTypes: [
                'card',
                'us_bank_account',
            ]
        );

        $this->cache->set(
            "checkout_session_{$user->getGuid()}",
            json_encode([
                'user_guid' => $user->getGuid(),
                'plan_id' => $planId,
                'time_period' => $timePeriod->value,
                'add_on_ids' => $addOnIds,
            ]),
            self::CACHE_TTL
        );

        return $checkoutSession->url;
    }


}
