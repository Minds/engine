<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repository;

class DomainService
{
    public function __construct(
        private Config $config,
        private Repository $repository,
        private PsrWrapper $cache,
    ) {
        
    }

    public function getTenantFromDomain(string $domain): ?Tenant
    {
        $domain = strtolower($domain);

        // Does the domain match
        if ($this->isReservedDomain($domain)) {
            // Nothing more to do, this is a reserved domain and not a multi tenant site
            throw new ReservedDomainException();
        }

        $cacheKey = 'global:tenant:domain:' . $domain;

        if ($tenant = $this->cache->get($cacheKey)) {
            return unserialize($tenant);
        }

        // Is this a temporary subdomain?
        if ($this->isTemporarySubdomain($domain)) {
            $tenant = $this->getTenantFromSubdomain($domain);
        } else {
            // Is this a custom domain?
            // Find the tenant id configs for this site
            $tenant = $this->repository->getTenantFromDomain($domain);
        }

        if (!$tenant) {
            throw new NoTenantFoundException("Could not find a valid site for domain: " . $domain);
        }

        $this->cache->set($cacheKey, serialize($tenant));

        return $tenant;
    }

    /**
     * Helper function to determine if a domain is a reserved domain (ie. for minds.com)
     * Also, treat ip addresses as reserved domains
     */
    protected function isReservedDomain(string $domain): bool
    {
        $reservedDomains = $this->config->get('multi_tenant')['reserved_domains'] ?? [];

        return in_array($domain, $reservedDomains, true)
            || filter_var($domain, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Helper function to determine if a domain is a subdomain
     */
    protected function isTemporarySubdomain(string $domain): bool
    {
        $domainSuffix = $this->config->get('multi_tenant')['subdomain_suffix'] ?? 'minds.com';

        return strpos($domain, $domainSuffix) !== false;
    }

    /**
     * Returns a tenant id from its subdomain
     */
    protected function getTenantFromSubdomain($domain): Tenant
    {
        $domainSuffix = $this->config->get('multi_tenant')['subdomain_suffix'] ?? 'minds.com';

        if (!$this->isTemporarySubdomain($domain)) {
            throw new \Exception("Not a valid subdomain");
        }

        $hash = rtrim(str_replace($domainSuffix, '', $domain), '.');

        return $this->repository->getTenantFromHash($hash);
    }

}
