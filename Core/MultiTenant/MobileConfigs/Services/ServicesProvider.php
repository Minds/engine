<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Services;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\MobileConfigs\Delegates\MobileAppPreviewReadyEmailDelegate;
use Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            MobileConfigAssetsService::class,
            fn (Di $di): MobileConfigAssetsService => new MobileConfigAssetsService(
                $di->get('Media\Imagick\Manager'),
                $di->get(Config::class),
                $di->get(MultiTenantBootService::class),
                $di->get(MultiTenantConfigManager::class)
            )
        );

        $this->di->bind(
            MobileConfigReaderService::class,
            fn (Di $di): MobileConfigReaderService => new MobileConfigReaderService(
                mobileConfigRepository: $di->get(MobileConfigRepository::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
                config: $di->get(Config::class)
            )
        );

        $this->di->bind(
            MobileConfigManagementService::class,
            fn (Di $di): MobileConfigManagementService => new MobileConfigManagementService(
                mobileConfigRepository: $di->get(MobileConfigRepository::class),
                mobilePreviewHandler: $di->get(MobilePreviewHandler::class),
                mobileAppPreviewReadyEmailDelegate: $di->get(MobileAppPreviewReadyEmailDelegate::class),
            )
        );

        $this->di->bind(
            MobileAppPreviewQRCodeService::class,
            fn (Di $di): MobileAppPreviewQRCodeService => new MobileAppPreviewQRCodeService(
                mobileConfigRepository: $di->get(MobileConfigRepository::class),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
                config: $di->get(Config::class)
            )
        );
    }
}
