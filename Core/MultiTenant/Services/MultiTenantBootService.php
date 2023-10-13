<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

class MultiTenantBootService
{
    private $rootConfigs = [];

    public function __construct(
        private Config $config,
        private DomainService $domainService,
        private MultiTenantDataService $dataService,
    ) {
        
    }

    /**
     * If a multi tenant install is found, this function will update all the site configs
     */
    public function bootFromRequest(ServerRequest $request): void
    {
        $uri = $request->getUri();

        $scheme = $uri->getScheme();
        $domain = $uri->getHost();
        $port = $uri->getPort();

        try {
            $tenant = $this->domainService->getTenantFromDomain($domain);
        } catch (ReservedDomainException) {
            // Nothing more to do, this is a reserved domain and not a multi tenant site
            return;
        } catch (NoTenantFoundException $e) {
            throw $e;
        }

        // Update the configs
    
        $this->setupConfigs(
            tenant: $tenant,
            scheme: $scheme,
            port: $port,
        );
    }

    /**
     * Use this boot method if you are running via a runner snd you know the tenant id
     */
    public function bootFromTenantId(int $tenantId): void
    {
        $tenant = $this->dataService->getTenantFromId($tenantId);

        if (!$tenant) {
            throw new NoTenantFoundException();
        }

        $this->setupConfigs($tenant);
    }

    /**
     * Resets Configs to its root state
     */
    public function resetRootConfigs(): void
    {
        $this->config->set('tenant_id', null);
        foreach ($this->rootConfigs as $key => $value) {
            $this->config->set($key, $value);
        }
    }

    private function setupConfigs(
        Tenant $tenant,
        string $scheme = 'https',
        ?int $port = null
    ): void {
        $domain = $this->domainService->buildDomain($tenant);

        if ($port) {
            $siteUrl = "$scheme://$domain:$port/";
        } else {
            $siteUrl = "$scheme://$domain/";
        }

        $this->setConfig('site_url', $siteUrl);
        $this->setConfig('cdn_url', $siteUrl);
        $this->setConfig('cdn_assets_url', $siteUrl);

        $this->setConfig('tenant_id', $tenant->id);

        $this->setConfig('dataroot', $this->config->get('dataroot') . 'tenant/' . $this->config->get('tenant_id') . '/');
    }

    private function setConfig(string $key, mixed $value): void
    {
        // If not a multi tenant, then we will save the configs for resetting later (if needed)
        if (!$this->config->get('tenant_id')) {
            $this->rootConfigs[$key] = $this->config->get($key);
        }
        $this->config->set($key, $value);
    }

}
