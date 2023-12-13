<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Subscriptions\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Stripe\StripeClient;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            SubscriptionsService::class,
            fn (Di $di): SubscriptionsService => new SubscriptionsService(
                stripeClient: $this->di->get(StripeClient::class, ['stripe_version' => '2020-08-27'])
            )
        );
    }
}
