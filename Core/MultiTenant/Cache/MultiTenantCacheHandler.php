<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Cache;

use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Psr\SimpleCache\InvalidArgumentException;

class MultiTenantCacheHandler
{
    public function __construct(
        private readonly PsrWrapper $cache,
    ) {
    }

    /**
     * @param string $key
     * @param bool $useTenantPrefix
     * @return mixed|null
     * @throws InvalidArgumentException
     */
    public function getKey(string $key, bool $useTenantPrefix = false): mixed
    {
        return $this->cache->withTenantPrefix($useTenantPrefix)->get($key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param bool $useTenantPrefix
     * @return bool
     * @throws InvalidArgumentException
     */
    public function setKey(string $key, mixed $value, bool $useTenantPrefix = false): bool
    {
        return $this->cache->withTenantPrefix($useTenantPrefix)->set($key, $value);
    }

    /**
     * @param string $key
     * @param bool $useTenantPrefix
     * @return bool
     * @throws InvalidArgumentException
     */
    public function deleteKey(string $key, bool $useTenantPrefix = false): bool
    {
        return $this->cache->withTenantPrefix($useTenantPrefix)->delete($key) ?? true;
    }

    /**
     * @param Tenant $tenant
     * @param DomainService|null $domainService
     * @param bool $useTenantPrefix
     * @return bool
     * @throws InvalidArgumentException
     */
    public function resetTenantCache(
        Tenant         $tenant,
        ?DomainService $domainService = null,
        bool           $useTenantPrefix = false
    ): bool {
        $domain = $domainService?->buildDomain($tenant) ?? $tenant->domain;

        $cacheKey = 'global:tenants:domain:' . $domain;

        $this->cache->withTenantPrefix($useTenantPrefix)->set($cacheKey, serialize($tenant));
        return true;
    }
}
