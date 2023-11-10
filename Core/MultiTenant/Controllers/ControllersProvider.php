<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Core\MultiTenant\Services\TenantUsersService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(TenantsController::class, function (Di $di): TenantsController {
            return new TenantsController(
                $di->get(TenantsService::class)
            );
        });
        $this->di->bind(TenantUsersController::class, function (Di $di): TenantUsersController {
            return new TenantUsersController(
                $di->get(TenantUsersService::class)
            );
        });
        $this->di->bind(FeaturedEntitiesController::class, function (Di $di): FeaturedEntitiesController {
            return new FeaturedEntitiesController(
                $di->get(FeaturedEntityService::class)
            );
        });

        $this->di->bind(DomainsController::class, function (Di $di): DomainsController {
            return new DomainsController(
                $di->get(DomainService::class)
            );
        });
    }
}
