<?php

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;
use Psr\Http\Message\ServerRequestInterface;

class MultiTenantBootService
{
    private $rootConfigs = [];

    private Tenant $tenant;

    public function __construct(
        private Config $config,
        private DomainService $domainService,
        private MultiTenantDataService $dataService,
    ) {
    }

    /**
     * If a multi tenant install is found, this function will update all the site configs
     */
    public function bootFromRequest(ServerRequestInterface $request): void
    {
        $uri = $request->getUri();

        $scheme = $request->getHeader('X-FORWARDED-PROTO') === 'https' ? 'https' : $uri->getScheme();
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

        $this->tenant = $tenant;
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

        $this->tenant = $tenant;
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

    /**
     * Returns the booted tenant
     */
    public function getTenant(): Tenant
    {
        return $this->tenant;
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

        // Base urls

        $this->setConfig('site_url', $siteUrl);
        $this->setConfig('cdn_url', $siteUrl);
        $this->setConfig('cdn_assets_url', $siteUrl);

        // Fediverse / Nostr / DID

        $didConfig = $this->config->get('did') ?? [];
        $didConfig['domain'] = $domain;
        $this->setConfig('did', $didConfig);

        // Nake global the tenant object

        $this->setConfig('tenant', $tenant);

        // System user guid

        $this->setConfig('system_user_guid', $tenant->rootUserGuid);

        // Data root

        $this->setConfig('dataroot', $this->config->get('dataroot') . 'tenant/' . $tenant->id . '/');

        // PostHog

        $postHogConfig = $this->config->get('posthog');
        if (isset($this->config->get('multi_tenant')['posthog'])) {
            $postHogConfig['api_key'] = $this->config->get('multi_tenant')['posthog']['api_key'];
            $postHogConfig['project_id'] = $this->config->get('multi_tenant')['posthog']['project_id'];
            $this->setConfig('posthog', $postHogConfig);
        }

        // Chatwoot

        if (isset($this->config->get('multi_tenant')['chatwoot'])) {
            $chatwootConfig = $this->config->get('chatwoot');
            $chatwootConfig['website_token'] = $this->config->get('multi_tenant')['chatwoot']['website_token'];
            $chatwootConfig['signing_key'] = $this->config->get('multi_tenant')['chatwoot']['signing_key'];
            $this->setConfig('chatwoot', $chatwootConfig);
        }

        // Misc

        if ($tenantConfig = $tenant->config) {
            $emailConfig = $this->config->get('email');

            if ($tenantConfig->siteEmail || $tenantConfig->siteName) {
                if ($tenantConfig->siteEmail) {
                    $emailConfig['sender']['email'] = $tenant->config->siteEmail;
                } else {
                    $emailConfig['sender']['email'] = 'no-reply@minds.com';
                }

                if ($tenantConfig->siteName) {
                    $emailConfig['sender']['name'] = $tenant->config->siteName;
                }

                if (isset($tenant->config->replyEmail)) {
                    if ($tenant->config->replyEmail === '') {
                        $emailConfig['sender']['reply_to'] = 'no-reply@minds.com';
                    } else {
                        $emailConfig['sender']['reply_to'] =  $tenant->config->replyEmail;
                    }
                }

                $this->setConfig('email', $emailConfig);
            }

            if ($tenantConfig->siteName) {
                $this->setConfig('site_name', $tenant->config->siteName);
            }

            $themeConfig = [];

            if ($tenant->config->colorScheme) {
                $themeConfig['color_scheme'] = $tenant->config->colorScheme?->value;
            }

            if ($tenant->config->primaryColor) {
                $themeConfig['primary_color'] = $tenant->config->primaryColor;
            }

            if (isset($tenant->config->lastCacheTimestamp)) {
                $this->setConfig('lastcache', $tenant->config->lastCacheTimestamp);
            }

            $this->setConfig('theme_override', [
                'color_scheme' => $tenant->config->colorScheme?->value,
                'primary_color' => $tenant->config->primaryColor
            ]);

            $this->setConfig('nsfw_enabled', isset($tenant->config->nsfwEnabled) ? $tenant->config->nsfwEnabled : true);
        }

        // Tenant ID (must be last so that rootConfigs are still saved)

        $this->setConfig('tenant_id', $tenant->id);
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
