<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repository;

class MultiTenantDataService
{
    public function __construct(
        private Repository $repository
    ) {
        
    }

    /**
     * Returns a Tenant Model from a custom domain
     */
    public function getTenantFromDomain(string $domain): ?Tenant
    {
        return $this->repository->getTenantFromDomain($domain);
    }

    /**
     * Returns a Tenant Model from a subdomain hash (md5 of the id)
     */
    public function getTenantFromHash(string $hash): ?Tenant
    {
        return $this->repository->getTenantFromHash($hash);
    }

    /**
     * Returns a Tenant Model from a Tenant Id
     */
    public function getTenantFromId(int $id): ?Tenant
    {
        return $this->repository->getTenantFromId($id);
    }

    public function getTenants(
        int $limit = 12,
        int $offset = 0,
        ?int $ownerGuid = null,
    ): iterable {
        return $this->repository->getTenants(
            limit: $limit,
            offset: $offset,
            ownerGuid: $ownerGuid,
        );
    }
}
