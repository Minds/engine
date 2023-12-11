<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Strapi\Services\StrapiService;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            CheckoutContentService::class,
            fn (Di $di): CheckoutContentService => new CheckoutContentService(
                strapiService: $di->get(StrapiService::class),
                stripeProductService: $di->get(StripeProductService::class),
                stripeProductPriceService: $di->get(StripeProductPriceService::class),
                cache: $di->get('Cache\Cassandra'),
            ),
        );
        $this->di->bind(
            CheckoutService::class,
            fn (Di $di): CheckoutService => new CheckoutService(
                stripeCheckoutManager: $di->get(StripeCheckoutManager::class),
                productPriceService: $di->get(StripeProductPriceService::class),
                cache: $di->get('Cache\Cassandra'),
            ),
        );
    }
}
