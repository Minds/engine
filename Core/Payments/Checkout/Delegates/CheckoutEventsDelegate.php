<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Entities\User;

class CheckoutEventsDelegate
{
    public function __construct(
        private readonly PostHogService $postHogService
    ) {
    }

    /**
     * @param User $user
     * @param string $productId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param array $addonIds
     * @return void
     */
    public function sendCheckoutPaymentEvent(
        User                   $user,
        string                 $productId,
        CheckoutTimePeriodEnum $timePeriod,
        array                  $addonIds = []
    ): void {
        $this->postHogService->withUser($user)->capture([
            'event' => 'user_checkout_payment',
            'checkout_product_id' => $productId,
            'checkout_time_period' => $timePeriod,
            'checkout_addon_ids' => $addonIds,
        ]);
    }

    /**
     * @param User $user
     * @param string $productId
     * @param CheckoutTimePeriodEnum $timePeriod
     * @param array $addonIds
     * @return void
     */
    public function sendCheckoutCompletedEvent(
        User                   $user,
        string                 $productId,
        CheckoutTimePeriodEnum $timePeriod,
        array                  $addonIds = []
    ): void {
        $this->postHogService->withUser($user)->capture([
            'event' => 'user_checkout_com',
            'checkout_product_id' => $productId,
            'checkout_time_period' => $timePeriod,
            'checkout_addon_ids' => $addonIds,
        ]);
    }
}
