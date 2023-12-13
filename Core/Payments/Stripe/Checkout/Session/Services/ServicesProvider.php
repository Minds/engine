<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Session\Services;

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
            SessionService::class,
            fn (Di $di): SessionService => new SessionService(
                stripeClient: $di->get(StripeClient::class, ['stripe_version' => '2020-08-27']),
            )
        );
    }
}
