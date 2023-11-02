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
use Minds\Exceptions\ServerErrorException;

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
    public function getConfigs(
    ): ?MultiTenantConfig {
        $tenantId = $this->config->get('tenant_id');

        if (!$tenantId) {
            throw new ServerErrorException('There is no tenant id set. Ensure that you are on a tenant domain.');
        }

        try {
            return $this->repository->get(
                tenantId: $tenantId,
            );
        } catch(NotFoundException $e) {
            return null;
        } catch(\Exception $e) {
            $this->logger->error($e);
            return null;
        }
    }

    /**
     * Sets multi-tenant config for the calling tenant.
     * @param ?string $siteName - site name to set.
     * @param ?MultiTenantColorScheme $colorScheme - color scheme to set.
     * @param ?string $primaryColor - primary color to set.
     * @param ?string $communityGuidelines - community guidelines.
     * @return bool - true on success.
     */
    public function upsertConfigs(
        ?string $siteName = null,
        ?MultiTenantColorScheme $colorScheme = null,
        ?string $primaryColor = null,
        ?string $communityGuidelines = null,
        ?string $expoProjectId = null
    ): bool {
        $tenantId = $this->config->get('tenant_id');

        $result = $this->repository->upsert(
            tenantId: $tenantId,
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
            communityGuidelines: $communityGuidelines,
            expoProjectId: $expoProjectId
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
        $domain = $this->domainService->buildDomain($tenant);
        $this->domainService->invalidateGlobalTenantCache($domain);
    }
}
