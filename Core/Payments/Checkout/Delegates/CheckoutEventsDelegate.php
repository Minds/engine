<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Delegates;

use Minds\Core\Analytics\Snowplow\Contexts\SnowplowNetworkCheckoutContext;
use Minds\Core\Analytics\Snowplow\Enums\SnowplowCheckoutEventTypeEnum;
use Minds\Core\Analytics\Snowplow\Events\SnowplowCheckoutEvent;
use Minds\Core\Analytics\Snowplow\Manager as SnowplowManager;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use Minds\Entities\User;

class CheckoutEventsDelegate
{
    public function __construct(
        private readonly SnowplowManager $snowplowManager
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
        $event = (new SnowplowCheckoutEvent(
            checkoutEventType: SnowplowCheckoutEventTypeEnum::CHECKOUT_PAYMENT
        ))
            ->setContext([
                new SnowplowNetworkCheckoutContext(
                    productId: $productId,
                    timePeriod: $timePeriod,
                    addonIds: $addonIds
                )
            ]);

        $this->snowplowManager
            ->setSubject($user)
            ->emit($event);
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
        $event = (new SnowplowCheckoutEvent(
            checkoutEventType: SnowplowCheckoutEventTypeEnum::CHECKOUT_PAYMENT
        ))
            ->setContext([
                new SnowplowNetworkCheckoutContext(
                    productId: $productId,
                    timePeriod: $timePeriod,
                    addonIds: $addonIds
                )
            ]);

        $this->snowplowManager
            ->setSubject($user)
            ->emit($event);
    }
}
