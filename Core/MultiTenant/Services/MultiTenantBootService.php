<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Zend\Diactoros\ServerRequestFactory;

class MultiTenantBootService
{
    public function __construct(
        private Config $config,
        private DomainService $domainService,
    ) {
        
    }

    public function boot(): void
    {
        $request = ServerRequestFactory::fromGlobals();

        $uri = $request->getUri();

        $scheme = $uri->getScheme();
        $domain = $uri->getHost();
        $port = $uri->getPort();

        try {
            $tenant = $this->domainService->getTenantFromDomain($domain);
            if ($tenant->domain) {
                $domain = $tenant->domain;
            }
        } catch (ReservedDomainException) {
            // Nothing more to do, this is a reserved domain and not a multi tenant site
            return;
        }

        // Update the configs

        if ($port) {
            $siteUrl = "$scheme://$domain:$port/";
        } else {
            $siteUrl = "$scheme://$domain/";
        }

        $this->config->set('site_url', $siteUrl);
        $this->config->set('cdn_url', $siteUrl);
        $this->config->set('cdn_assets_url', $siteUrl);

        $this->config->set('tenant_id', $tenant->id);

        $this->config->set('dataroot', $this->config->get('dataroot') . 'tenant/' . $this->config->get('tenant_id') . '/');
    }

}
