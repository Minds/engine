<?php
namespace Minds\Core\MultiTenant\Services;

use Exception;
use Minds\Core\Config\Config;
use Zend\Diactoros\ServerRequestFactory;

class MultiTenantBootService
{
    public function __construct(
        private Config $config
    ) {
        
    }

    public function boot(): void
    {
        $request = ServerRequestFactory::fromGlobals();

        $domain = $request->getUri()->getHost();
    
        // Does the domain match
        if ($this->isReservedDomain($domain)) {
            // Nothing more to do, this is a reserved domain and not a multi tenant site
            return;
        }

        // Find the tenant id configs for this site
        

        // Update the configs

        $siteUrl = 'https://' . $domain . '/';

        $this->config->set('site_url', $siteUrl);
        $this->config->set('cdn_url', $siteUrl);
        $this->config->set('cdn_assets_url', $siteUrl);

        $this->config->set('tenant_id', 123);
    }

    protected function isReservedDomain(string $domain): bool
    {
        $reservedDomains = $this->config->get('multi_tenant')['reserved_domains'] ?? [];

        return in_array($domain, $reservedDomains, true);
    }
}
