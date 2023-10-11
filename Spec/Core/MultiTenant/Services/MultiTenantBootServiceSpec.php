<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\ServerRequest;

class MultiTenantBootServiceSpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $domainServiceMock;

    public function let(Config $configMock, DomainService $domainServiceMock)
    {
        $this->beConstructedWith($configMock, $domainServiceMock);
        $this->configMock = $configMock;
        $this->domainServiceMock = $domainServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MultiTenantBootService::class);
    }

    public function it_should_setup_a_tenant(ServerRequest $requestMock, UriInterface $uriMock)
    {
        $requestMock->getUri()->willReturn($uriMock);

        $uriMock->getScheme()
            ->willReturn('http');
        
        $uriMock->getHost()
            ->willReturn('phpspec.local');

        $uriMock->getPort()->willReturn(null);

        $this->domainServiceMock->getTenantFromDomain('phpspec.local')
            ->shouldBeCalled()
            ->willReturn(new Tenant(123, 'phpspec.local'));

        // test the configs are being applied

        $this->configMock->set('site_url', 'http://phpspec.local/')
            ->shouldBeCalled();

        $this->configMock->set('cdn_url', 'http://phpspec.local/')
            ->shouldBeCalled();

        $this->configMock->set('cdn_assets_url', 'http://phpspec.local/')
            ->shouldBeCalled();

        $this->configMock->set('tenant_id', 123)
            ->shouldBeCalled();

        $this->configMock->set('dataroot', '/dataroot/tenant/123/')
            ->shouldBeCalled();

        $this->configMock->get('dataroot')
            ->shouldBeCalled()
            ->willReturn('/dataroot/');

        $this->configMock->get('tenant_id')
            ->willReturn(123);

        $this->withRequest($requestMock)->boot();
    }
}
