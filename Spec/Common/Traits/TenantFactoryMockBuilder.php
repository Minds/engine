<?php

namespace Spec\Minds\Common\Traits;

use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use ReflectionClass;

/**
 * Mock builders for tenant models.
 */
trait TenantFactoryMockBuilder
{
    /**
     * Generate a tenant mock.
     * @param int|null $id
     * @param string|null $domain
     * @param int|null $ownerGuid
     * @param int|null $rootUserGuid
     * @param MultiTenantConfig|null $config
     * @param TenantPlanEnum|null $plan
     * @param int|null $trialStartTimestamp
     * @return Tenant
     */
    private function generateTenantMock(
        ?int $id = 1234567890123456,
        ?string $domain = 'example.minds.com',
        ?int $ownerGuid = 2234567890123456,
        ?int $rootUserGuid = 3234567890123456,
        ?MultiTenantConfig $config = null,
        ?TenantPlanEnum $plan = TenantPlanEnum::COMMUNITY,
        ?int $trialStartTimestamp = null
    ): Tenant {
        $tenantMockFactory = new ReflectionClass(Tenant::class);
        $tenant = $tenantMockFactory->newInstanceWithoutConstructor();

        $tenantMockFactory->getProperty('id')->setValue($tenant, $id);
        $tenantMockFactory->getProperty('domain')->setValue($tenant, $domain);
        $tenantMockFactory->getProperty('ownerGuid')->setValue($tenant, $ownerGuid);
        $tenantMockFactory->getProperty('rootUserGuid')->setValue($tenant, $rootUserGuid);
        $tenantMockFactory->getProperty('config')->setValue($tenant, $config);
        $tenantMockFactory->getProperty('plan')->setValue($tenant, $plan);
        $tenantMockFactory->getProperty('trialStartTimestamp')->setValue($tenant, $trialStartTimestamp);

        return $tenant;
    }

    /**
     * Generate a tenant config mock.
     * @param string|null $siteName
     * @param string|null $siteEmail
     * @param MultiTenantColorScheme|null $colorScheme
     * @param string|null $primaryColor
     * @param bool|null $federationDisabled
     * @param string|null $replyEmail
     * @param bool|null $nsfwEnabled
     * @param bool|null $boostEnabled
     * @param bool|null $customHomePageEnabled
     * @param string|null $customHomePageDescription
     * @param bool|null $walledGardenEnabled
     * @param int|null $updatedTimestamp
     * @param int|null $lastCacheTimestamp
     * @return MultiTenantConfig
     */
    private function generateTenantConfigMock(
        ?string $siteName = 'Example Tenant',
        ?string $siteEmail = 'noreply@minds.com',
        ?MultiTenantColorScheme $colorScheme = MultiTenantColorScheme::LIGHT,
        ?string $primaryColor = '#000000',
        ?bool $federationDisabled = false,
        ?string $replyEmail = 'noreply@minds.com',
        ?bool $nsfwEnabled = false,
        ?bool $boostEnabled = false,
        ?bool $customHomePageEnabled = false,
        ?string $customHomePageDescription = 'Custom homepage description',
        ?bool $walledGardenEnabled = false,
        ?int $updatedTimestamp = null,
        ?int $lastCacheTimestamp = null
    ): MultiTenantConfig {
        $tenantConfigMockFactory = new ReflectionClass(MultiTenantConfig::class);
        $tenantConfig = $tenantConfigMockFactory->newInstanceWithoutConstructor();

        $tenantConfigMockFactory->getProperty('siteName')->setValue($tenantConfig, $siteName);
        $tenantConfigMockFactory->getProperty('siteEmail')->setValue($tenantConfig, $siteEmail);
        $tenantConfigMockFactory->getProperty('colorScheme')->setValue($tenantConfig, $colorScheme);
        $tenantConfigMockFactory->getProperty('primaryColor')->setValue($tenantConfig, $primaryColor);
        $tenantConfigMockFactory->getProperty('federationDisabled')->setValue($tenantConfig, $federationDisabled);
        $tenantConfigMockFactory->getProperty('replyEmail')->setValue($tenantConfig, $replyEmail);
        $tenantConfigMockFactory->getProperty('nsfwEnabled')->setValue($tenantConfig, $nsfwEnabled);
        $tenantConfigMockFactory->getProperty('boostEnabled')->setValue($tenantConfig, $boostEnabled);
        $tenantConfigMockFactory->getProperty('customHomePageEnabled')->setValue($tenantConfig, $customHomePageEnabled);
        $tenantConfigMockFactory->getProperty('customHomePageDescription')->setValue($tenantConfig, $customHomePageDescription);
        $tenantConfigMockFactory->getProperty('walledGardenEnabled')->setValue($tenantConfig, $walledGardenEnabled);
        $tenantConfigMockFactory->getProperty('updatedTimestamp')->setValue($tenantConfig, $updatedTimestamp);
        $tenantConfigMockFactory->getProperty('lastCacheTimestamp')->setValue($tenantConfig, $lastCacheTimestamp);

        return $tenantConfig;
    }

    /**
     * Generate a mobile config mock.
     * @param int|null $updateTimestamp
     * @param MobileSplashScreenTypeEnum|null $splashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $welcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $previewStatus
     * @param int|null $previewLastUpdatedTimestamp
     * @param string|null $productionAppVersion
     * @param string|null $appVersion
     * @param string|null $easProjectId
     * @param string|null $appSlug
     * @param string|null $appScheme
     * @param string|null $appIosBundle
     * @param string|null $appAndroidPackage
     * @param string|null $androidKeystoreFingerprint
     * @param string|null $appleDevelopmentTeamId
     * @param bool|null $appTrackingMessageEnabled
     * @param string|null $appTrackingMessage
     * @return MobileConfig
     */
    private function generateMobileConfigMock(
        ?int $updateTimestamp = 0,
        ?MobileSplashScreenTypeEnum $splashScreenType = MobileSplashScreenTypeEnum::CONTAIN,
        ?MobileWelcomeScreenLogoTypeEnum $welcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::SQUARE,
        ?MobilePreviewStatusEnum $previewStatus = MobilePreviewStatusEnum::READY,
        ?int $previewLastUpdatedTimestamp = 0,
        ?string $productionAppVersion = '1.0.0',
        ?string $appVersion = '1.0.0',
        ?string $easProjectId = 'eas-project-id',
        ?string $appSlug = 'app-slug',
        ?string $appScheme = 'app-scheme',
        ?string $appIosBundle = 'app-ios-bundle',
        ?string $appAndroidPackage = 'app-android-package',
        ?string $androidKeystoreFingerprint = 'android-keystore-fingerprint',
        ?string $appleDevelopmentTeamId = 'apple-development-team-id',
        ?bool $appTrackingMessageEnabled = true,
        ?string $appTrackingMessage = 'Allow this app to collect app-related data that can be used for tracking you or your device.',
    ): MobileConfig {
        $mobileConfigMockFactory = new ReflectionClass(MobileConfig::class);
        $mobileConfig = $mobileConfigMockFactory->newInstanceWithoutConstructor();

        $mobileConfigMockFactory->getProperty('updateTimestamp')->setValue($mobileConfig, $updateTimestamp);
        $mobileConfigMockFactory->getProperty('splashScreenType')->setValue($mobileConfig, $splashScreenType);
        $mobileConfigMockFactory->getProperty('welcomeScreenLogoType')->setValue($mobileConfig, $welcomeScreenLogoType);
        $mobileConfigMockFactory->getProperty('previewStatus')->setValue($mobileConfig, $previewStatus);
        $mobileConfigMockFactory->getProperty('previewLastUpdatedTimestamp')->setValue($mobileConfig, $previewLastUpdatedTimestamp);
        $mobileConfigMockFactory->getProperty('productionAppVersion')->setValue($mobileConfig, $productionAppVersion);
        $mobileConfigMockFactory->getProperty('appVersion')->setValue($mobileConfig, $appVersion);
        $mobileConfigMockFactory->getProperty('easProjectId')->setValue($mobileConfig, $easProjectId);
        $mobileConfigMockFactory->getProperty('appSlug')->setValue($mobileConfig, $appSlug);
        $mobileConfigMockFactory->getProperty('appScheme')->setValue($mobileConfig, $appScheme);
        $mobileConfigMockFactory->getProperty('appIosBundle')->setValue($mobileConfig, $appIosBundle);
        $mobileConfigMockFactory->getProperty('appAndroidPackage')->setValue($mobileConfig, $appAndroidPackage);
        $mobileConfigMockFactory->getProperty('androidKeystoreFingerprint')->setValue($mobileConfig, $androidKeystoreFingerprint);
        $mobileConfigMockFactory->getProperty('appleDevelopmentTeamId')->setValue($mobileConfig, $appleDevelopmentTeamId);
        $mobileConfigMockFactory->getProperty('appTrackingMessageEnabled')->setValue($mobileConfig, $appTrackingMessageEnabled);
        $mobileConfigMockFactory->getProperty('appTrackingMessage')->setValue($mobileConfig, $appTrackingMessage);

        return $mobileConfig;
    }
}
