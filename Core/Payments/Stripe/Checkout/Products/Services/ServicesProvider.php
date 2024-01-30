<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Stripe\Checkout\Products\Services;

use Minds\Core\Config\Config;
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
            ProductPriceService::class,
            fn(Di $di): ProductPriceService => new ProductPriceService(
                stripeClient: $di->get(StripeClient::class, ['stripe_version' => '2020-08-27']),
                cache: $di->get('Cache')
            )
        );

        $this->di->bind(
            ProductService::class,
            fn(Di $di): ProductService => new ProductService(
                stripeClient: $di->get(StripeClient::class, ['stripe_version' => '2020-08-27']),
                cache: $di->get('Cache'),
                config: $di->get(Config::class)
            )
        );
    }
}
