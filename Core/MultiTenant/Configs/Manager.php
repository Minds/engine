<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Exceptions\NotFoundException;

/**
 * Manager for multi-tenant configs.
 * Allows getting and updating of config values.
 */
class Manager
{
    public function __construct(
        private readonly MultiTenantDataService $multiTenantDataService,
        private readonly DomainService $domainService,
        private readonly Repository $repository,
        private readonly Logger $logger,
        private readonly Config $config
    ) {
    }

    /**
     * Gets multi-tenant config for the calling tenant.
     * @return ?MultiTenantConfig - null if not found.
     */
    public function getConfigs(): ?MultiTenantConfig
    {
        $tenantId = $this->config->get('tenant_id');

        try {
            return $this->repository->get(
                tenantId: $tenantId,
            );
        } catch (NotFoundException $e) {
            return null;
        } catch (\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Sets multi-tenant config for the calling tenant.
     * @param ?string $siteName - site name to set.
     * @param ?MultiTenantColorScheme $colorScheme - color scheme to set.
     * @param ?string $primaryColor - primary color to set.
     * @param ?string $customScript - custom script to set.
     * @param ?bool $federationDisabled - federation disabled.
     * @param ?string $replyEmail - reply-to email address.
     * @param ?bool $nsfwEnabled - whether nfsw reporting tools are enabled.
     * @param ?bool $boostEnabled - whether boosting is enabled.
     * @param ?bool $customHomePageEnabled - whether custom home page is enabled.
     * @param ?bool $customHomePageDescription - custom home page description.
     * @param ?bool $walledGardenEnabled - whether walled garden mode is enabled.
     * @param ?bool $digestEmailEnabled - whether digest emails are enabled.
     * @param ?bool $welcomeEmailEnabled - whether welcome emails are enabled.
     * @param ?string $loggedInLandingPageIdWeb - logged in landing page ID for web.
     * @param ?string $loggedInLandingPageIdMobile - logged in landing page ID for mobile.
     * @param ?bool $isNonProfit - whether the tenant is a non-profit.
     * @param ?int $lastCacheTimestamp - last cache timestamp.
     * @return bool - true on success.
     */
    public function upsertConfigs(
        ?string $siteName = null,
        ?MultiTenantColorScheme $colorScheme = null,
        ?string $primaryColor = null,
        ?string $customScript = null,
        ?bool $federationDisabled = null,
        ?string $replyEmail = null,
        ?bool $nsfwEnabled = null,
        ?bool $boostEnabled = null,
        ?bool $customHomePageEnabled = null,
        ?string $customHomePageDescription = null,
        ?bool $walledGardenEnabled = null,
        ?bool $digestEmailEnabled = null,
        ?bool $welcomeEmailEnabled = null,
        ?string $loggedInLandingPageIdWeb = null,
        ?string $loggedInLandingPageIdMobile = null,
        ?bool $isNonProfit = null,
        ?int $lastCacheTimestamp = null
    ): bool {
        $tenantId = $this->config->get('tenant_id');

        $result = $this->repository->upsert(
            tenantId: $tenantId,
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
            customScript: $customScript,
            federationDisabled: $federationDisabled,
            replyEmail: $replyEmail,
            nsfwEnabled: $nsfwEnabled,
            boostEnabled: $boostEnabled,
            customHomePageEnabled: $customHomePageEnabled,
            customHomePageDescription: $customHomePageDescription,
            walledGardenEnabled: $walledGardenEnabled,
            digestEmailEnabled: $digestEmailEnabled,
            welcomeEmailEnabled: $welcomeEmailEnabled,
            loggedInLandingPageIdWeb: $loggedInLandingPageIdWeb,
            loggedInLandingPageIdMobile: $loggedInLandingPageIdMobile,
            isNonProfit: $isNonProfit,
            lastCacheTimestamp: $lastCacheTimestamp
        );

        if ($result) {
            $this->invalidateCache($tenantId);
        }

        return $result;
    }

    /**
     * Invalidates global cached configurations for a given tenant domain.
     * @param integer $tenantId - tenant ID.
     * @return void
     */
    private function invalidateCache(int $tenantId): void
    {
        $tenant = $this->multiTenantDataService->getTenantFromId($tenantId);
        $this->domainService->invalidateGlobalTenantCache($tenant);
    }
}
