<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Delegates;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Payments\Stripe\Customers\ManagerV2 as StripeCustomersManager;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;

class DelegatesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            CheckoutEventsDelegate::class,
            fn (Di $di): CheckoutEventsDelegate => new CheckoutEventsDelegate(
                postHogService: $di->get(PostHogService::class),
                stripeCustomersManager: $di->get('Stripe\Customers\ManagerV2'),
            )
        );
    }
}
