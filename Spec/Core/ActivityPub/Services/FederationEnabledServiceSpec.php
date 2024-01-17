<?php
declare(strict_types=1);

namespace Spec\Minds\Core\ActivityPub\Services;

use Minds\Core\ActivityPub\Services\FederationEnabledService;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class FederationEnabledServiceSpec extends ObjectBehavior
{
    /** @var MultiTenantBootService */
    private Collaborator $multiTenantBootService;

    /** @var Config */
    private Collaborator $config;

    public function let(
        MultiTenantBootService $multiTenantBootService,
        Config $config
    ) {
        $this->multiTenantBootService = $multiTenantBootService;
        $this->config = $config;

        $this->beConstructedWith(
            $multiTenantBootService,
            $config
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(FederationEnabledService::class);
    }

    public function it_should_return_true_if_tenant_id_is_not_set(): void
    {
        $this->config->get('tenant_id')->willReturn(null);

        $this->isEnabled()->shouldReturn(true);
    }

    public function it_should_return_false_if_on_tenant_site_and_federation_is_disabled(): void
    {
        $tenant = new Tenant(
            id: 1,
            domain: 'test.com',
            config: new MultiTenantConfig(
                federationDisabled: true
            )
        );

        $this->config->get('tenant_id')->willReturn(1);
        $this->multiTenantBootService->getTenant()->willReturn($tenant);

        $this->isEnabled()->shouldReturn(false);
    }

    public function it_should_return_false_if_on_tenant_site_but_no_custom_domain(): void
    {
        $tenant = new Tenant(
            id: 1,
            config: new MultiTenantConfig(
                federationDisabled: true
            )
        );

        $this->config->get('tenant_id')->willReturn(1);
        $this->multiTenantBootService->getTenant()->willReturn($tenant);

        $this->isEnabled()->shouldReturn(false);
    }

    public function it_should_return_true_if_federation_is_enabled_on_tenant_network(): void
    {
        $tenant = new Tenant(
            id: 1,
            domain: 'test.com',
            config: new MultiTenantConfig(
                federationDisabled: false
            )
        );

        $this->config->get('tenant_id')->willReturn(1);
        $this->multiTenantBootService->getTenant()->willReturn($tenant);

        $this->isEnabled()->shouldReturn(true);
    }
}
