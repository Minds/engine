<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\GraphQL\Types\KeyValuePair;
use Minds\Core\MultiTenant\Enums\MobileConfigImageTypeEnum;
use Minds\Core\MultiTenant\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\Types\AppReadyMobileConfig;
use Minds\Core\MultiTenant\Types\MobileConfig;

class MobileConfigReaderService
{
    public function __construct(
        private readonly MobileConfigRepository $mobileConfigRepository,
        private readonly MultiTenantDataService $multiTenantDataService,
    ) {

    }

    /**
     * @param int $tenantId
     * @return AppReadyMobileConfig
     * @throws NoMobileConfigFoundException
     */
    public function getAppReadyMobileConfig(int $tenantId): AppReadyMobileConfig
    {
        $tenant = $this->multiTenantDataService->getTenantFromId($tenantId);
        $mobileConfig = $this->mobileConfigRepository->getMobileConfig($tenantId);

        return new AppReadyMobileConfig(
            appName: $tenant->config?->siteName ?? '',
            tenantId: $tenant->id,
            appHost: $tenant->domain,
            appSplashResize: strtolower($mobileConfig->splashScreenType->name),
            accentColorLight: $tenant->config?->primaryColor ?? '',
            accentColorDark: $tenant->config?->primaryColor ?? '',
            welcomeLogoType: strtolower($mobileConfig->welcomeScreenLogoType->name),
            theme: strtolower($tenant->config?->colorScheme ?? ''),
            apiUrl: $tenant->domain,
            assets: $this->prepareAppReadyMobileConfigAssets($tenant)
        );
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
                value: $tenant->domain . "api/v3/multi-tenant/mobile-configs/image/$imageType->value"
            );
        }

        return $assets;
    }
}
