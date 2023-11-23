<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\UriInterface;
use Zend\Diactoros\ServerRequest;

class MultiTenantBootServiceSpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $domainServiceMock;
    private Collaborator $dataServiceMock;

    public function let(
        Config $configMock,
        DomainService $domainServiceMock,
        MultiTenantDataService $dataServiceMock,
    ) {
        $this->beConstructedWith($configMock, $domainServiceMock, $dataServiceMock);
        $this->configMock = $configMock;
        $this->domainServiceMock = $domainServiceMock;
        $this->dataServiceMock = $dataServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MultiTenantBootService::class);
    }

    public function it_should_setup_a_tenant(ServerRequest $requestMock, UriInterface $uriMock)
    {
        $siteName = 'Test site';
        $siteEmail = 'noreply@minds.com';
        $colorScheme = MultiTenantColorScheme::DARK;
        $primaryColor = '#fff000';
        $updatedTimestamp = time();

        $this->configMock->get('email')
            ->shouldBeCalled()
            ->willReturn(
                [
                'sender' => [
                    'email' => $siteEmail
                ]
            ]
            );

        $requestMock->getHeader('X-FORWARDED-PROTO')
            ->willReturn(null);

        $requestMock->getUri()->willReturn($uriMock);

        $uriMock->getScheme()
            ->willReturn('http');
        
        $uriMock->getHost()
            ->willReturn('phpspec.local');

        $uriMock->getPort()->willReturn(null);

        $this->domainServiceMock->getTenantFromDomain('phpspec.local')
            ->shouldBeCalled()
            ->willReturn(new Tenant(
                id: 123,
                domain: 'phpspec.local',
                ownerGuid: 234,
                config: new MultiTenantConfig(
                    siteName: $siteName,
                    siteEmail: $siteEmail,
                    colorScheme: $colorScheme,
                    primaryColor: $primaryColor,
                    updatedTimestamp: $updatedTimestamp
                )
            ));

        $this->domainServiceMock->buildDomain(Argument::any())
            ->willReturn('phpspec.local');

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

        $this->configMock->set('email', [
            'sender' => [
                'email' => $siteEmail,
                "name" => $siteName
            ]
        ])->shouldBeCalled();

        $this->configMock->set('site_name', $siteName)
            ->shouldBeCalled();

        $this->configMock->set('theme_override', [
            'color_scheme' => $colorScheme->value,
            'primary_color' => $primaryColor
        ])->shouldBeCalled();

        $this->bootFromRequest($requestMock);
    }
}
