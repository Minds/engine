<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\Data\cache\PsrWrapper;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\Http\Cloudflare\Client as CloudflareClient;
use Minds\Core\MultiTenant\Repositories\DomainsRepository;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class DomainServiceSpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $dataServiceMock;
    private Collaborator $cacheMock;

    public function let(
        Config $configMock,
        MultiTenantDataService $dataServiceMock,
        PsrWrapper $cacheMock,
        CloudflareClient $cloudflareClientMock,
        DomainsRepository $domainsRepositoryMock,
    )
    {
        $this->beConstructedWith($configMock, $dataServiceMock, $cacheMock, $cloudflareClientMock, $domainsRepositoryMock);
        $this->configMock = $configMock;
        $this->dataServiceMock = $dataServiceMock;
        $this->cacheMock = $cacheMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(DomainService::class);
    }

    public function it_should_return_a_tenant_from_custom_domain()
    {
        $this->dataServiceMock->getTenantFromDomain('phpspec.local')
            ->willReturn(new Tenant(
                id: 123,
                domain: 'phpspec.local'
            ));

        $tenant = $this->getTenantFromDomain('phpspec.local');
        $tenant->id->shouldBe(123);
        $tenant->domain->shouldBe('phpspec.local');
    }

    public function it_should_return_a_tenant_from_subdomain()
    {
        $this->configMock->get('multi_tenant')
            ->willReturn([
                'subdomain_suffix' => 'networks.phpspec.local',
            ]);

        $this->dataServiceMock->getTenantFromHash('202cb962ac59075b964b07152d234b70')
            ->willReturn(new Tenant(
                id: 123,
                domain: null,
            ));

        $tenant = $this->getTenantFromDomain('202cb962ac59075b964b07152d234b70.networks.phpspec.local');
        $tenant->id->shouldBe(123);
        $tenant->domain->shouldBe(null);
    }

    public function it_should_not_return_tenant_if_reserved_domain()
    {
        $this->configMock->get('multi_tenant')
            ->willReturn([
                'reserved_domains' => [
                    'phpspec.public',
                ]
            ]);

        $this->shouldThrow(ReservedDomainException::class)->duringGetTenantFromDomain('phpspec.public');
    }

    public function it_should_build_custom_domain()
    {
        $tenant = new Tenant(
            id: 123,
            domain: 'custom.domain',
        );
        $this->buildDomain($tenant)->shouldbe('custom.domain');
    }

    public function it_should_build_temp_subdomain()
    {
        $tenant = new Tenant(
            id: 123,
        );
        $this->buildDomain($tenant)->shouldbe('202cb962ac59075b964b07152d234b70.minds.com');
    }

    public function it_should_invalidate_global_tenant_cache()
    {
        $domain = 'custom.domain';

        $this->cacheMock->withTenantPrefix(false)
            ->shouldBeCalled()
            ->willReturn($this->cacheMock);

        $this->cacheMock->delete("global:tenant:domain:$domain")
            ->shouldBeCalled();

        $this->invalidateGlobalTenantCache($domain);
    }
}
