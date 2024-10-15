<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Services;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;

/**
 * Service for managing the production app version.
 */
class ProductionAppVersionService
{
    public function __construct(
        private readonly MobileConfigRepository $repository,
        private readonly Logger $logger,
    ) {
    }

    /**
     * Sets the production mobile app version for a tenant.
     * @param int $tenantId - the tenant ID.
     * @param string|null $productionAppVersion - the production app version.
     * @return bool - true on success.
     */
    public function setProductionMobileAppVersion(int $tenantId, ?string $productionAppVersion): bool
    {
        try {
            $this->repository->storeMobileConfig(
                tenantId: $tenantId,
                productionAppVersion: $productionAppVersion
            );
            return true;
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }

    /**
     * Clears the production mobile app version for all tenants.
     * @return bool - true on success.
     */
    public function clearForAllTenants(): bool
    {
        try {
            return $this->repository->clearAllProductionMobileAppVersions();
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return false;
        }
    }
}
