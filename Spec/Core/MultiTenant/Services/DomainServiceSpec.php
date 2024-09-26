<?php

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\Http\Cloudflare\Client as CloudflareClient;
use Minds\Core\Http\Cloudflare\Enums\CustomHostnameStatusEnum;
use Minds\Core\Http\Cloudflare\Models\CustomHostname;
use Minds\Core\Http\Cloudflare\Models\CustomHostnameMetadata;
use Minds\Core\Http\Cloudflare\Models\CustomHostnameOwnershipVerification;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Enums\DnsRecordEnum;
use Minds\Core\MultiTenant\Exceptions\ReservedDomainException;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repositories\DomainsRepository;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Core\MultiTenant\Types\MultiTenantDomain;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use GuzzleHttp\Client;
use Minds\Core\Log\Logger;
use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\TransferException;
use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Entities\User;

class DomainServiceSpec extends ObjectBehavior
{
    private Collaborator $configMock;
    private Collaborator $dataServiceMock;
    private Collaborator $multiTenantCacheHandlerMock;
    private Collaborator $cloudflareClientMock;
    private Collaborator $domainsRepositoryMock;
    private Collaborator $httpClientMock;
    private Collaborator $loggerMock;
    private Collaborator $postHogServiceMock;

    private ReflectionClass $tenantMockFactory;

    public function let(
        Config                  $configMock,
        MultiTenantDataService  $dataServiceMock,
        MultiTenantCacheHandler $multiTenantCacheHandler,
        CloudflareClient        $cloudflareClientMock,
        DomainsRepository       $domainsRepositoryMock,
        Client                  $httpClientMock,
        Logger                  $loggerMock,
        PostHogService          $postHogServiceMock,
    ) {
        $this->tenantMockFactory = new ReflectionClass(Tenant::class);

        $this->beConstructedWith($configMock, $dataServiceMock, $multiTenantCacheHandler, $cloudflareClientMock, $domainsRepositoryMock, $httpClientMock, $loggerMock, $postHogServiceMock);
        $this->configMock = $configMock;
        $this->dataServiceMock = $dataServiceMock;
        $this->multiTenantCacheHandlerMock = $multiTenantCacheHandler;
        $this->cloudflareClientMock = $cloudflareClientMock;
        $this->domainsRepositoryMock = $domainsRepositoryMock;
        $this->httpClientMock = $httpClientMock;
        $this->loggerMock = $loggerMock;
        $this->postHogServiceMock = $postHogServiceMock;
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

        $this->multiTenantCacheHandlerMock->getKey('global:tenants:domain:phpspec.local')
            ->willReturn(null);

        $this->multiTenantCacheHandlerMock
            ->setKey(
                "global:tenants:domain:phpspec.local",
                Argument::type('string')
            )
            ->shouldBeCalled()
            ->willReturn(true);

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

        $this->multiTenantCacheHandlerMock->getKey('global:tenants:domain:202cb962ac59075b964b07152d234b70.networks.phpspec.local')
            ->willReturn(null);

        $this->multiTenantCacheHandlerMock
            ->setKey(
                "global:tenants:domain:202cb962ac59075b964b07152d234b70.networks.phpspec.local",
                Argument::type('string')
            )
            ->shouldBeCalled()
            ->willReturn(true);

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

    public function it_should_build_navigatable_custom_domain(
        ResponseInterface $response
    ) {
        $tenant = new Tenant(
            id: 123,
            domain: 'custom.domain',
        );

        $response->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(200);

        $this->httpClientMock->request('GET', $tenant->domain, ['timeout' => 10])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->buildNavigatableDomain($tenant)->shouldbe('custom.domain');
    }

    public function it_should_build_temp_subdomain_when_custom_domain_is_not_set()
    {
        $tenant = new Tenant(
            id: 123
        );

        $this->httpClientMock->request('GET', $tenant->domain, ['timeout' => 10])
            ->shouldNotBeCalled();

        $this->buildNavigatableDomain($tenant)->shouldbe('202cb962ac59075b964b07152d234b70.minds.com');
    }

    public function it_should_build_temp_subdomain_when_custom_domain_is_not_navigatable(
        ResponseInterface $response
    ) {
        $tenant = new Tenant(
            id: 123,
            domain: 'custom.domain',
        );

        $response->getStatusCode()
            ->shouldBeCalled()
            ->willReturn(500);

        $this->httpClientMock->request('GET', $tenant->domain, ['timeout' => 10])
            ->shouldBeCalled()
            ->willReturn($response);

        $this->buildNavigatableDomain($tenant)->shouldbe('202cb962ac59075b964b07152d234b70.minds.com');
    }

    public function it_should_build_temp_subdomain_when_custom_domain_request_throws_exception(

    ) {
        $tenant = new Tenant(
            id: 123,
            domain: 'custom.domain'
        );

        $this->httpClientMock->request('GET', $tenant->domain, ['timeout' => 10])
            ->shouldBeCalled()
            ->willThrow(new TransferException());

        $this->buildNavigatableDomain($tenant)->shouldbe('202cb962ac59075b964b07152d234b70.minds.com');
    }

    public function it_should_invalidate_global_tenant_cache()
    {
        $domain = 'custom.domain';

        $tenant = new Tenant(
            id: 123,
            domain: $domain
        );

        $this->multiTenantCacheHandlerMock->deleteKey("global:tenants:domain:$domain")
            ->shouldBeCalled()
            ->willReturn(true);

        $this->multiTenantCacheHandlerMock->deleteKey("global:tenants:domain:202cb962ac59075b964b07152d234b70.minds.com")
            ->shouldBeCalled()
            ->willReturn(true);

        $this->invalidateGlobalTenantCache($tenant)->shouldBe(true);
    }

    public function it_should_setup_a_hostname()
    {
        $this->configMock->get('tenant')
            ->willReturn(
                $this->generateTenantMock(
                    id: 1,
                    trialStartTimestamp: null
                )
            );
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->configMock->get('cloudflare')->willReturn([
            'custom_hostnames' => [
                'apex_ip' => '127.0.0.1',
                'cname_hostname' => 'set-me-up.minds.com',
            ]
        ]);

        $this->cloudflareClientMock->createCustomHostname('sub.example.com')
            ->willReturn(
                new CustomHostname(
                    id: 'id',
                    hostname: 'sub.example.com',
                    customOriginServer: '',
                    status: CustomHostnameStatusEnum::ACTIVE,
                    metadata: new CustomHostnameMetadata([]),
                    ownershipVerification: new CustomHostnameOwnershipVerification(
                        name: 'verify.sub.example.com',
                        type: 'txt',
                        value: 'id'
                    ),
                    createdAt: time(),
                )
            );

        $this->domainsRepositoryMock->storeDomainDetails(
            tenantId: 1,
            cloudflareId: 'id',
            domain: 'sub.example.com',
        )->shouldBeCalled();

        $this->postHogServiceMock->capture('setup-domain', Argument::type(User::class), [
            'domain' => 'sub.example.com'
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $domain = $this->setupCustomHostname('sub.example.com', new User());

        $domain->domain->shouldBe('sub.example.com');
        $domain->cloudflareId->shouldBe('id');
    }

    public function it_should_return_a_hostname()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->configMock->get('cloudflare')->willReturn([
            'custom_hostnames' => [
                'apex_ip' => '127.0.0.1',
                'cname_hostname' => 'set-me-up.minds.com',
            ]
        ]);

        $this->cloudflareClientMock->getCustomHostnameDetails('id')
            ->willReturn(
                new CustomHostname(
                    id: 'id',
                    hostname: 'sub.example.com',
                    customOriginServer: '',
                    status: CustomHostnameStatusEnum::ACTIVE,
                    metadata: new CustomHostnameMetadata([]),
                    ownershipVerification: new CustomHostnameOwnershipVerification(
                        name: 'verify.sub.example.com',
                        type: 'txt',
                        value: 'id'
                    ),
                    createdAt: time(),
                )
            );

        $this->domainsRepositoryMock->getDomainDetails(
            tenantId: 1,
        )->willReturn(new MultiTenantDomain(
            tenantId: 1,
            domain: 'sub.example.com',
            cloudflareId: 'id',
        ));

        $domain = $this->getCustomHostname('sub.example.com');

        $domain->domain->shouldBe('sub.example.com');
        $domain->cloudflareId->shouldBe('id');
    }

    public function it_should_update_a_hostname()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->configMock->get('cloudflare')->willReturn([
            'custom_hostnames' => [
                'apex_ip' => '127.0.0.1',
                'cname_hostname' => 'set-me-up.minds.com',
            ]
        ]);

        $this->domainsRepositoryMock->getDomainDetails(
            tenantId: 1,
        )->willReturn(new MultiTenantDomain(
            tenantId: 1,
            domain: 'sub.example.com',
            cloudflareId: 'id',
        ));

        $this->cloudflareClientMock->getCustomHostnameDetails('id')
            ->willReturn(
                new CustomHostname(
                    id: 'id',
                    hostname: 'sub.example.com',
                    customOriginServer: '',
                    status: CustomHostnameStatusEnum::ACTIVE,
                    metadata: new CustomHostnameMetadata([]),
                    ownershipVerification: new CustomHostnameOwnershipVerification(
                        name: 'verify.sub.example.com',
                        type: 'txt',
                        value: 'id'
                    ),
                    createdAt: time(),
                )
            );

        $this->cloudflareClientMock->updateCustomHostnameDetails('id', 'sub.example.com')
            ->willReturn(
                new CustomHostname(
                    id: 'id',
                    hostname: 'sub.example.com',
                    customOriginServer: '',
                    status: CustomHostnameStatusEnum::ACTIVE,
                    metadata: new CustomHostnameMetadata([]),
                    ownershipVerification: new CustomHostnameOwnershipVerification(
                        name: 'verify.sub.example.com',
                        type: 'txt',
                        value: 'id'
                    ),
                    createdAt: time(),
                )
            );

        $this->domainsRepositoryMock->storeDomainDetails(
            tenantId: 1,
            cloudflareId: 'id',
            domain: 'sub.example.com',
        )->shouldBeCalled();

        $this->postHogServiceMock->capture('update-domain', Argument::type(User::class), [
            'domain' => 'sub.example.com'
        ])
            ->shouldBeCalled()
            ->willReturn(true);

        $domain = $this->updateCustomHostname('sub.example.com', new User());

        $domain->domain->shouldBe('sub.example.com');
        $domain->cloudflareId->shouldBe('id');
    }

    public function it_should_return_cname_record()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->configMock->get('cloudflare')->willReturn([
            'custom_hostnames' => [
                'apex_ip' => '127.0.0.1',
                'cname_hostname' => 'set-me-up.minds.com',
            ]
        ]);

        $this->cloudflareClientMock->getCustomHostnameDetails('id')
            ->willReturn(
                new CustomHostname(
                    id: 'id',
                    hostname: 'sub.example.com',
                    customOriginServer: '',
                    status: CustomHostnameStatusEnum::ACTIVE,
                    metadata: new CustomHostnameMetadata([]),
                    ownershipVerification: new CustomHostnameOwnershipVerification(
                        name: 'verify.sub.example.com',
                        type: 'txt',
                        value: 'id'
                    ),
                    createdAt: time(),
                )
            );

        $this->domainsRepositoryMock->getDomainDetails(
            tenantId: 1,
        )->willReturn(new MultiTenantDomain(
            tenantId: 1,
            domain: 'sub.example.com',
            cloudflareId: 'id',
        ));

        $domain = $this->getCustomHostname('sub.example.com');

        $domain->dnsRecord->type->shouldBe(DnsRecordEnum::CNAME);
        $domain->dnsRecord->value->shouldBe('set-me-up.minds.com');
    }

    public function it_should_return_a_record()
    {
        $this->configMock->get('tenant_id')
            ->willReturn(1);

        $this->configMock->get('cloudflare')->willReturn([
            'custom_hostnames' => [
                'apex_ip' => '127.0.0.1',
                'cname_hostname' => 'set-me-up.minds.com',
            ]
        ]);

        $this->cloudflareClientMock->getCustomHostnameDetails('id')
            ->willReturn(
                new CustomHostname(
                    id: 'id',
                    hostname: 'example.com',
                    customOriginServer: '',
                    status: CustomHostnameStatusEnum::ACTIVE,
                    metadata: new CustomHostnameMetadata([]),
                    ownershipVerification: new CustomHostnameOwnershipVerification(
                        name: 'verify.example.com',
                        type: 'txt',
                        value: 'id'
                    ),
                    createdAt: time(),
                )
            );

        $this->domainsRepositoryMock->getDomainDetails(
            tenantId: 1,
        )->willReturn(new MultiTenantDomain(
            tenantId: 1,
            domain: 'example.com',
            cloudflareId: 'id',
        ));

        $domain = $this->getCustomHostname('example.com');

        $domain->dnsRecord->type->shouldBe(DnsRecordEnum::A);
        $domain->dnsRecord->value->shouldBe('127.0.0.1');
    }

    public function it_should_build_tmp_subdomain()
    {
        $tenantId = 123;
        $hashedTenantId = md5($tenantId);
        $tenant = $this->generateTenantMock($tenantId);

        $this->configMock->get('multi_tenant')
            ->willReturn([
                'subdomain_suffix' => 'minds.com',
            ]);

        $this->buildTmpSubdomain($tenant)->shouldBe("{$hashedTenantId}.minds.com");
    }

    private function generateTenantMock(
        int      $id,
        int|null $trialStartTimestamp = null
    ): Tenant {
        $tenantMock = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('id')->setValue($tenantMock, $id);
        $this->tenantMockFactory->getProperty('trialStartTimestamp')->setValue($tenantMock, $trialStartTimestamp);

        return $tenantMock;
    }
}
