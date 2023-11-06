<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;

class DomainService
{
    public function __construct(
        private Config $config,
        private MultiTenantDataService $dataService,
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

        $cacheKey = $this->getCacheKey($domain);

        if ($tenant = $this->cache->get($cacheKey)) {
            return unserialize($tenant);
        }

        // Is this a temporary subdomain?
        if ($this->isTemporarySubdomain($domain)) {
            $tenant = $this->getTenantFromSubdomain($domain);
        } else {
            // Is this a custom domain?
            // Find the tenant id configs for this site
            $tenant = $this->dataService->getTenantFromDomain($domain);
        }

        if (!$tenant) {
            throw new NoTenantFoundException("Could not find a valid site for domain: " . $domain);
        }

        $this->cache->set($cacheKey, serialize($tenant));

        return $tenant;
    }

    /**
     * Builds the domain for the tenant.
     * If a custom domain is provided, we will return it.
     * If no domain, we fallback to a temporary subdomain
     */
    public function buildDomain(Tenant $tenant): string
    {
        if ($tenant->domain) {
            // Todo: Confirm DNS is configured correctly?
            return $tenant->domain;
        }

        $domainSuffix = $this->getDomainSuffix();

        return md5($tenant->id) . '.' . $domainSuffix;

    }

    /**
     * Invalidate the global tenant cache entry for a given domain.
     * @param string $domain - domain to scope invalidation to.
     * @return self
     */
    public function invalidateGlobalTenantCache(string $domain): self
    {
        $this->cache->withTenantPrefix(false)->delete($this->getCacheKey($domain));
        return $this;
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
        $domainSuffix = $this->getDomainSuffix();

        return strpos($domain, $domainSuffix) !== false;
    }

    /**
     * Returns a tenant id from its subdomain
     */
    protected function getTenantFromSubdomain($domain): ?Tenant
    {
        $domainSuffix = $this->getDomainSuffix();

        if (!$this->isTemporarySubdomain($domain)) {
            throw new \Exception("Not a valid subdomain");
        }

        $hash = rtrim(str_replace($domainSuffix, '', $domain), '.');

        return $this->dataService->getTenantFromHash($hash);
    }

    /**
     * This is the domain suffix for the temporary network domains.
     * ie. elephants.(networks.minds.com)
     */
    protected function getDomainSuffix(): string
    {
        return $this->config->get('multi_tenant')['subdomain_suffix'] ?? 'minds.com';
    }

    /**
     * Get cache key for a given domain.
     * @param string $domain - domain to scope cache key to.
     * @return string - cache key.
     */
    protected function getCacheKey(string $domain): string
    {
        return strtolower('global:tenant:domain:' . $domain);
    }
}
