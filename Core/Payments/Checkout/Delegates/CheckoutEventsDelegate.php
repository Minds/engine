<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as StripeCustomersManager;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Entities\User;

class CheckoutEventsDelegate
{
    public function __construct(
        private readonly PostHogService $postHogService,
        private readonly StripeCustomersManager $stripeCustomersManager,
    ) {
    }

    /**
     * @param User $user
     * @param string $productId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param string[] $addonIds
     * @return void
     */
    public function sendCheckoutPaymentEvent(
        User                   $user,
        string                 $productId,
        CheckoutTimePeriodEnum $timePeriod,
        array                  $addonIds = []
    ): void {
        $this->postHogService->capture(
            event: 'checkout_payment',
            user: $user,
            properties: [
                'checkout_product_id' => $productId,
                'checkout_time_period' => $timePeriod->name,
                'checkout_addons' => $addonIds,
            ],
            setOnce: [
                'stripe_customer_id' => $this->getStripeCustomerId($user),
            ]
        );
    }

    /**
     * @param User $user
     * @param string $productId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param string[] $addonIds
     * @return void
     */
    public function sendCheckoutCompletedEvent(
        User                   $user,
        string                 $productId,
        CheckoutTimePeriodEnum $timePeriod,
        array                  $addonIds = []
    ): void {
        $this->postHogService->capture(
            event: 'checkout_complete',
            user: $user,
            properties: [
                'checkout_product_id' => $productId,
                'checkout_time_period' => $timePeriod->name,
                'checkout_addons' => $addonIds,
            ],
            setOnce: [
                'stripe_customer_id' => $this->getStripeCustomerId($user),
            ]
        );
    }

    /**
     * Returns the stripe id for the customer
     */
    private function getStripeCustomerId(User $user): string
    {
        return $this->stripeCustomersManager->getByUser($user)->id;
    }
}
