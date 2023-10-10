<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Repository;

class DomainService
{
    public function __construct(
        private Config $config,
        private Repository $repository,
        private PsrWrapper $cache,
    ) {
        
    }

    public function getTenantIdFromDomain(string $domain): ?int
    {
        $domain = strtolower($domain);

        // Does the domain match
        if ($this->isReservedDomain($domain)) {
            // Nothing more to do, this is a reserved domain and not a multi tenant site
            return null;
        }

        $cacheKey = 'global:tenant:domain:' . $domain;

        if ($tenantId = $this->cache->get($cacheKey)) {
            return (int) $tenantId;
        }

        // Find the tenant id configs for this site
        $tenantId = $this->repository->getTenantIdFromDomain($domain);

        if (!$tenantId) {
            throw new NoTenantFoundException("Could not find a valid site for this domain");
        }

        $this->cache->set($cacheKey, $tenantId);

        return $tenantId;
    }

    protected function isReservedDomain(string $domain): bool
    {
        $reservedDomains = $this->config->get('multi_tenant')['reserved_domains'] ?? [];

        return in_array($domain, $reservedDomains, true);
    }

}
