<?php

namespace Spec\Minds\Common\Traits;

use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
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
}
