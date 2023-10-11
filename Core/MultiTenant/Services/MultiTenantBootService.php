<?php
namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\ServerRequestFactory;

class MultiTenantBootService
{
    private ServerRequest $request;

    public function __construct(
        private Config $config,
        private DomainService $domainService,
    ) {
        
    }

    /**
     * Pass through a request interface so the boot function knows what domain we are calling
     * from
     */
    public function withRequest(ServerRequest $request): MultiTenantBootService
    {
        $instance = clone $this;
        $instance->request = $request;
        return $instance;
    }

    /**
     * If a multi tenant install is found, this function will update all the site configs
     */
    public function boot(): void
    {
        $uri = $this->request->getUri();

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
        } catch (NoTenantFoundException $e) {
            throw $e;
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
