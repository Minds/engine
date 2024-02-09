<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService as StripeCustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            SiteMembershipManagementService::class,
            fn (Di $di): SiteMembershipManagementService => new SiteMembershipManagementService(
                siteMembershipRepository: $di->get(SiteMembershipRepository::class),
                siteMembershipGroupsRepository: $di->get(SiteMembershipGroupsRepository::class),
                siteMembershipRolesRepository: $di->get(SiteMembershipRolesRepository::class),
                stripeProductService: $di->get(StripeProductService::class),
                config: $di->get(Config::class)
            )
        );
        $this->di->bind(
            SiteMembershipReaderService::class,
            fn (Di $di): SiteMembershipReaderService => new SiteMembershipReaderService(
                siteMembershipRepository: $di->get(SiteMembershipRepository::class),
                siteMembershipGroupsRepository: $di->get(SiteMembershipGroupsRepository::class),
                siteMembershipRolesRepository: $di->get(SiteMembershipRolesRepository::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
            )
        );
        $this->di->bind(
            SiteMembershipSubscriptionsService::class,
            fn (Di $di): SiteMembershipSubscriptionsService => new SiteMembershipSubscriptionsService(
                siteMembershipSubscriptionsRepository: $di->get(SiteMembershipSubscriptionsRepository::class),
                siteMembershipReaderService: $di->get(SiteMembershipReaderService::class),
                stripeCheckoutManager: $di->get(StripeCheckoutManager::class),
                stripeProductService: $di->get(StripeProductService::class),
                stripeProductPriceService: $di->get(StripeProductPriceService::class),
                stripeCheckoutSessionService: $di->get(StripeCheckoutSessionService::class),
            )
        );

        $this->di->bind(
            SiteMembershipSubscriptionsManagementService::class,
            fn (Di $di): SiteMembershipSubscriptionsManagementService => new SiteMembershipSubscriptionsManagementService(
                siteMembershipSubscriptionsRepository: $di->get(SiteMembershipSubscriptionsRepository::class),
                stripeSubscriptionsService: $di->get(StripeSubscriptionsService::class),
                stripeCustomerPortalService: $di->get(StripeCustomerPortalService::class),
                config: $di->get(Config::class)
            )
        );
    }
}
