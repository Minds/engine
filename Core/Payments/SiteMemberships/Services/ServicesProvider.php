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
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductPriceService as StripeProductPriceService;
use Minds\Core\Payments\Stripe\Checkout\Products\Services\ProductService as StripeProductService;

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
                stripeProductService: $di->get(StripeProductService::class)
            )
        );
        $this->di->bind(
            SiteMembershipReaderService::class,
            fn (Di $di): SiteMembershipReaderService => new SiteMembershipReaderService(
                siteMembershipRepository: $di->get(SiteMembershipRepository::class),
                siteMembershipGroupsRepository: $di->get(SiteMembershipGroupsRepository::class),
                siteMembershipRolesRepository: $di->get(SiteMembershipRolesRepository::class),
                stripeProductService: $di->get(StripeProductService::class),
                stripeProductPriceService: $di->get(StripeProductPriceService::class),
                entitiesBuilder: $di->get('EntitiesBuilder'),
                config: $di->get(Config::class)
            )
        );
    }
}
