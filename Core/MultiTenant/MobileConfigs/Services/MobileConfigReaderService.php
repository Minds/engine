<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Services;

use Minds\Core\Config\Config;
use Minds\Core\GraphQL\Types\KeyValuePair;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileConfigImageTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Types\AppReadyMobileConfig;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

class MobileConfigReaderService
{
    public function __construct(
        private readonly MobileConfigRepository $mobileConfigRepository,
        private readonly MultiTenantBootService $multiTenantBootService,
        private readonly Config                 $config
    ) {

    }

    /**
     * @param int $tenantId
     * @return AppReadyMobileConfig
     * @throws NoTenantFoundException
     */
    public function getAppReadyMobileConfig(int $tenantId): AppReadyMobileConfig
    {
        $this->multiTenantBootService->bootFromTenantId($tenantId);
        $tenant = $this->multiTenantBootService->getTenant();
        try {
            $mobileConfig = $this->mobileConfigRepository->getMobileConfig($tenantId);
        } catch (NoMobileConfigFoundException $e) {
            $mobileConfig = new MobileConfig(
                updateTimestamp: time(),
            );
        }

        $config = new AppReadyMobileConfig(
            appName: $tenant->config?->siteName ?? '',
            tenantId: $tenant->id,
            appHost: $this->config->get('site_url'),
            appSplashResize: strtolower($mobileConfig->splashScreenType->name),
            accentColorLight: $tenant->config?->primaryColor ?? '',
            accentColorDark: $tenant->config?->primaryColor ?? '',
            welcomeLogoType: strtolower($mobileConfig->welcomeScreenLogoType->name),
            theme: strtolower($tenant->config?->colorScheme->value ?? ''),
            apiUrl: $this->config->get('site_url'),
            assets: $this->prepareAppReadyMobileConfigAssets($tenant),
            easProjectId: $mobileConfig->easProjectId,
            appSlug: $mobileConfig->appSlug,
            appScheme: $mobileConfig->appScheme,
            appIosBundle: $mobileConfig->appIosBundle,
            appAndroidPackage: $mobileConfig->appAndroidPackage,
        );

        $this->multiTenantBootService->resetRootConfigs();
        return $config;
    }

    /**
     * @return MobileConfig
     */
    public function getMobileConfig(): MobileConfig
    {
        try {
            return $this->mobileConfigRepository->getMobileConfig();
        } catch (NoMobileConfigFoundException $e) {
            return new MobileConfig(
                updateTimestamp: time(),
            );
        }
    }

    /**
     * @param Tenant $tenant
     * @return array
     */
    private function prepareAppReadyMobileConfigAssets(Tenant $tenant): array
    {
        $assets = [];

        foreach (MobileConfigImageTypeEnum::cases() as $imageType) {
            $assets[] = new KeyValuePair(
                key: $imageType->value,
                value: "{$this->config->get('site_url')}api/v3/multi-tenant/mobile-configs/image/$imageType->value" . "?" . time()
            );
        }

        return $assets;
    }
}
