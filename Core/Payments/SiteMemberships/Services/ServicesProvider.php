<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Services;

use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipGroupsRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipRolesRepository;
use Minds\Core\Payments\SiteMemberships\Repositories\SiteMembershipSubscriptionsRepository;
use Minds\Core\Payments\Stripe\Checkout\Manager as StripeCheckoutManager;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;
use Minds\Core\Payments\Stripe\Checkout\Session\Services\SessionService as StripeCheckoutSessionService;
use Minds\Core\Payments\Stripe\CustomerPortal\Services\CustomerPortalService as StripeCustomerPortalService;
use Minds\Core\Payments\Stripe\Subscriptions\Services\SubscriptionsService as StripeSubscriptionsService;
use Minds\Core\Payments\Stripe\Webhooks\Services\SubscriptionsWebhookService;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipService;

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
                stripeCheckoutSessionService: $di->get(StripeCheckoutSessionService::class),
                config: $di->get(Config::class),
                groupMembershipService: $di->get(GroupMembershipService::class),
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

        $this->di->bind(
            SiteMembershipsRenewalsService::class,
            fn (Di $di): SiteMembershipsRenewalsService => new SiteMembershipsRenewalsService(
                subscriptionsWebhookService: $di->get(SubscriptionsWebhookService::class),
                siteMembershipSubscriptionsService: $di->get(SiteMembershipSubscriptionsService::class),
                stripeSubscriptionsService: $di->get(StripeSubscriptionsService::class),
                logger: $di->get('Logger')
            )
        );
        
        $this->di->bind(
            SiteMembershipBatchService::class,
            fn (Di $di) => new SiteMembershipBatchService(
                entitiesBuilder: $di->get(EntitiesBuilder::class),
                oidcUserService: $di->get(OidcUserService::class),
                tenantUsersRepository: $di->get(TenantUsersRepository::class),
                config: $di->get(Config::class),
                readerService: $di->get(SiteMembershipReaderService::class),
                siteMembershipSubscriptionsService: $di->get(SiteMembershipSubscriptionsService::class),
                logger: $di->get('Logger'),
            )
        );
    }
}
