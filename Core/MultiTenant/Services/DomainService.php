<?php
namespace Minds\Core\MultiTenant\Services;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\Http\Cloudflare\Client as CloudflareClient;
use Minds\Core\Http\Cloudflare\Models\CustomHostnameOwnershipVerification;
use Minds\Core\Http\Cloudflare\Models\CustomHostname;
use Minds\Core\MultiTenant\Enums\DnsRecordEnum;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repositories\DomainsRepository;
use Minds\Core\MultiTenant\Types\MultiTenantDomain;
use Minds\Core\MultiTenant\Types\MultiTenantDomainDnsRecord;
use Psr\SimpleCache\InvalidArgumentException;

class DomainService
{
    public function __construct(
        private readonly Config $config,
        private readonly MultiTenantDataService $dataService,
        private readonly PsrWrapper $cache,
        private readonly CloudflareClient $cloudflareClient,
        private readonly DomainsRepository $domainsRepository
    ) {
        
    }

    /**
     * @param string $domain
     * @return Tenant|null
     * @throws NoTenantFoundException
     * @throws ReservedDomainException
     * @throws InvalidArgumentException
     */
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

    /**
     * Sets up a custom hostname and returns a model with the DNS records required
     * @throws GuzzleException
     * @throws Exception
     */
    public function setupCustomHostname(string $hostname): MultiTenantDomain
    {
        $tenantId = $this->config->get('tenant_id');
        $customHostname = $this->cloudflareClient->createCustomHostname($hostname);

        $this->domainsRepository->storeDomainDetails(
            tenantId: $tenantId,
            cloudflareId: $customHostname->id,
            domain: $customHostname->hostname,
        );

        $multiTenantDomain = $this->buildMultiTenantDomainFromCfCustomHostname($tenantId, $customHostname);

        return $multiTenantDomain;
    }

    /**
     * Returns DNS records and status of the hostname
     * @return MultiTenantDomain
     * @throws Exception
     */
    public function getCustomHostname(): MultiTenantDomain
    {
        $tenantId = $this->config->get('tenant_id');
    
        $multiTenantDomain = $this->domainsRepository->getDomainDetails(
            tenantId: $tenantId
        );

        $customHostname = $this->cloudflareClient->getCustomHostnameDetails($multiTenantDomain->cloudflareId);

        $multiTenantDomain = $this->buildMultiTenantDomainFromCfCustomHostname($tenantId, $customHostname);

        return $multiTenantDomain;
    }

    /**
     * Updates a hostname. This will delete and recreate the record on Cloudflare
     * @throws Exception
     */
    public function updateCustomHostname(string $hostname): MultiTenantDomain
    {
        $tenantId = $this->config->get('tenant_id');
        $currentDomain = $this->getCustomHostname();

        $customHostname = $this->cloudflareClient->updateCustomHostnameDetails(
            cloudflareId: $currentDomain->cloudflareId,
            hostname: $hostname
        );

        $this->domainsRepository->storeDomainDetails(
            tenantId: $tenantId,
            cloudflareId: $customHostname->id,
            domain: $customHostname->hostname,
        );

        $multiTenantDomain = $this->buildMultiTenantDomainFromCfCustomHostname($tenantId, $customHostname);

        return $multiTenantDomain;
    }

    /**
     * Builds a MultiTenantDomain model from a cloudflare model
     */
    private function buildMultiTenantDomainFromCfCustomHostname(
        int $tenantId,
        CustomHostname $customHostname
    ): MultiTenantDomain {
        return new MultiTenantDomain(
            tenantId: $tenantId,
            domain: $customHostname->hostname,
            status: $customHostname->status,
            cloudflareId: $customHostname->id,
            dnsRecord: $this->buildDnsRecord($customHostname->hostname),
            ownershipVerificationDnsRecord: $this->buildOwnershipVerificationDnsRecord($customHostname->ownershipVerification)
        );
    }

    /**
     * Returns the DNS record that a custom should point to.
     * Apex domains will use a static ip, subdomains will use a cname
     */
    private function buildDnsRecord(string $hostname): MultiTenantDomainDnsRecord
    {
        $parts = explode('.', $hostname);
        $isApex = count($parts) == 2;

        $cloudflareConfig = $this->config->get('cloudflare')['custom_hostnames'] ?? [
            'apex_ip' => '127.0.0.1',
            'cname_hostname' => 'set-me-up.minds.com',
        ];

        if ($isApex) {
            return new MultiTenantDomainDnsRecord($hostname, DnsRecordEnum::A, $cloudflareConfig['apex_ip']);
        } else {
            return new MultiTenantDomainDnsRecord($hostname, DnsRecordEnum::CNAME, $cloudflareConfig['cname_hostname']);
        }
    }

    /**
     * Returns the TXT record that a user needs to apply to their domain
     */
    private function buildOwnershipVerificationDnsRecord(CustomHostnameOwnershipVerification $ownershipVerification): MultiTenantDomainDnsRecord
    {
        return new MultiTenantDomainDnsRecord(
            name: $ownershipVerification->name,
            type: DnsRecordEnum::from($ownershipVerification->type),
            value: $ownershipVerification->value
        );
    }
}
