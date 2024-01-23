<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\MobileConfigs\Controllers\MobileConfigManagementController;
use Minds\Core\MultiTenant\MobileConfigs\Controllers\MobileConfigPreviewPsrController;
use Minds\Core\MultiTenant\MobileConfigs\Controllers\MobileConfigPsrController;
use Minds\Core\MultiTenant\MobileConfigs\Controllers\MobileConfigReaderController;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigAssetsService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;
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
                $di->get(TenantsService::class),
                $di->get('Experiments\Manager'),
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

        $this->di->bind(
            MobileConfigReaderController::class,
            fn (Di $di): MobileConfigReaderController => new MobileConfigReaderController(
                mobileConfigReaderService: $di->get(MobileConfigReaderService::class),
                jwt: new Jwt(),
                config: $di->get(Config::class)
            )
        );

        $this->di->bind(
            MobileConfigPsrController::class,
            fn (Di $di): MobileConfigPsrController => new MobileConfigPsrController(
                mobileConfigAssetsService: $di->get(MobileConfigAssetsService::class),
            )
        );

        $this->di->bind(
            MobileConfigPreviewPsrController::class,
            fn (Di $di): MobileConfigPreviewPsrController => new MobileConfigPreviewPsrController(
                mobileConfigManagementService: $di->get(MobileConfigManagementService::class),
                jwt: new Jwt(),
                config: $di->get(Config::class)
            )
        );

        $this->di->bind(
            MobileConfigManagementController::class,
            fn (Di $di): MobileConfigManagementController => new MobileConfigManagementController(
                mobileConfigManagementService: $di->get(MobileConfigManagementService::class)
            )
        );
    }
}
