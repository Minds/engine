<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\MultiTenant\Configs\Repository as TenantConfigRepository;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repository;

class TenantsService
{
    public function __construct(
        private readonly Repository $repository,
        private readonly TenantConfigRepository $tenantConfigRepository,
    ) {
    }

    /**
     * @param int $ownerGuid
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function getTenantsByOwnerGuid(
        int $ownerGuid,
        int $limit = 12,
        int $offset = 0,
    ): array {
        return iterator_to_array(
            iterator: $this->repository->getTenants(
                limit: $limit,
                offset: $offset,
                ownerGuid: $ownerGuid,
            )
        );
    }

    /**
     * @param Tenant $tenant
     * @return Tenant
     */
    public function createNetwork(Tenant $tenant): Tenant
    {
        $tenant = $this->repository->createTenant($tenant);

        if ($tenant->config) {
            $this->tenantConfigRepository->upsert(
                tenantId: $tenant->id,
                siteName: $tenant->config->siteName,
                colorScheme: $tenant->config->colorScheme,
                primaryColor: $tenant->config->primaryColor,
            );
        }

        return $tenant;
    }
}
