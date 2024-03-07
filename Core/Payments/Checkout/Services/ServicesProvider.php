<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\Payments\Checkout\Delegates\CheckoutEventsDelegate;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;
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
                persistentCache: $di->get('Cache\Cassandra'),
                cache: $di->get('Cache'),
                checkoutEventsDelegate: $di->get(CheckoutEventsDelegate::class),
            ),
        );
        $this->di->bind(
            CheckoutService::class,
            fn (Di $di): CheckoutService => new CheckoutService(
                stripeCheckoutManager: $di->get(StripeCheckoutManager::class),
                stripeProductPriceService: $di->get(StripeProductPriceService::class),
                stripeProductService: $di->get(StripeProductService::class),
                stripeCheckoutSessionService: $di->get(StripeCheckoutSessionService::class),
                tenantsService: $di->get(TenantsService::class),
                stripeSubscriptionsService: $di->get(StripeSubscriptionsService::class),
                cache: $di->get('Cache\Cassandra'),
                checkoutEventsDelegate: $di->get(CheckoutEventsDelegate::class),
                multiTenantCacheHandler: $di->get(MultiTenantCacheHandler::class),
            ),
        );
    }
}
