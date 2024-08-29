<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Analytics\PostHog\PostHogService;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Cache\MultiTenantCacheHandler;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Configs\Repository as TenantConfigRepository;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Repository;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\TenantsService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;
use ReflectionException;

class TenantsServiceSpec extends ObjectBehavior
{
    private Collaborator $repositoryMock;
    private Collaborator $tenantConfigRepositoryMock;
    private Collaborator $multiTenantCacheHandlerMock;
    private Collaborator $domainServiceMock;
    private Collaborator $mindsConfigMock;
    private Collaborator $postHogServiceMock;

    private ReflectionClass $tenantMockFactory;
    private ReflectionClass $tenantConfigMockFactory;

    public function let(
        Repository              $repository,
        TenantConfigRepository  $tenantConfigRepository,
        MultiTenantCacheHandler $multiTenantCacheHandler,
        DomainService           $domainService,
        Config                  $mindsConfig,
        PostHogService          $postHogServiceMock,
    ): void {
        $this->repositoryMock = $repository;
        $this->tenantConfigRepositoryMock = $tenantConfigRepository;
        $this->multiTenantCacheHandlerMock = $multiTenantCacheHandler;
        $this->domainServiceMock = $domainService;
        $this->mindsConfigMock = $mindsConfig;
        $this->postHogServiceMock = $postHogServiceMock;

        $this->tenantMockFactory = new ReflectionClass(Tenant::class);
        $this->tenantConfigMockFactory = new ReflectionClass(MultiTenantConfig::class);

        $this->beConstructedWith(
            $this->repositoryMock,
            $this->tenantConfigRepositoryMock,
            $this->multiTenantCacheHandlerMock,
            $this->domainServiceMock,
            $this->mindsConfigMock,
            $this->postHogServiceMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(TenantsService::class);
    }

    public function it_should_return_tenants_by_owner_guid(): void
    {
        $ownerGuid = 1;
        $limit = 12;
        $offset = 0;
        $this->repositoryMock->getTenants(
            limit: $limit,
            offset: $offset,
            ownerGuid: $ownerGuid
        )
            ->shouldBeCalledOnce()
            ->willYield([]);

        $this->getTenantsByOwnerGuid(
            ownerGuid: $ownerGuid,
            limit: $limit,
            offset: $offset
        )->shouldBeArray();
    }

    public function it_should_create_network_WITHOUT_config(): void
    {
        $tenant = $this->generateTenantMock(1, null);
        $this->mindsConfigMock->get('tenant_id')->willReturn(null);
        $this->repositoryMock->createTenant($tenant, false)->willReturn($tenant);
        $this->tenantConfigRepositoryMock->upsert(
            Argument::type('integer'),
            Argument::type('string'),
            Argument::type(MultiTenantColorScheme::class),
            Argument::type('string'),
        )->shouldNotBeCalled();

        $this->createNetwork($tenant)->shouldBe($tenant);
    }

    public function it_should_create_network_WITH_config(): void
    {
        $tenant = $this->generateTenantMock(
            1,
            $this->generateTenantConfigMock(
                'siteName',
                MultiTenantColorScheme::DARK,
                'primaryColor'
            )
        );
        $this->mindsConfigMock->get('tenant_id')->willReturn(null);
        $this->repositoryMock->createTenant($tenant, false)->willReturn($tenant);
        $this->tenantConfigRepositoryMock->upsert(
            1,
            $tenant->config->siteName,
            $tenant->config->colorScheme,
            $tenant->config->primaryColor,
        )->shouldBeCalledOnce();

        $this->createNetwork($tenant)->shouldBe($tenant);
    }

    public function it_should_create_network_trial_WITHOUT_config(
        User $userMock
    ): void {
        $tenant = $this->generateTenantMock(1, null);

        $this->repositoryMock->canHaveTrialTenant($userMock)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mindsConfigMock->get('tenant_id')->willReturn(null);
        $this->repositoryMock->createTenant($tenant, true)->willReturn($tenant);
        $this->tenantConfigRepositoryMock->upsert(
            Argument::type('integer'),
            Argument::type('string'),
            Argument::type(MultiTenantColorScheme::class),
            Argument::type('string'),
        )->shouldNotBeCalled();

        $this->postHogServiceMock->capture(
            'tenant_trial_start',
            $userMock,
            [
                'tenant_id' => 1,
            ],
            [],
            [
                'tenant_trial_started' => date('c', strtotime('midnight')),
            ]
        )
            ->willReturn(true);

        $this->createNetworkTrial($tenant, $userMock)->shouldBe($tenant);
    }

    public function it_should_create_network_trial_WITH_config(
        User $userMock
    ): void {
        $tenant = $this->generateTenantMock(
            1,
            $this->generateTenantConfigMock(
                'siteName',
                MultiTenantColorScheme::DARK,
                'primaryColor'
            )
        );

        $this->repositoryMock->canHaveTrialTenant($userMock)
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->mindsConfigMock->get('tenant_id')->willReturn(null);
        $this->repositoryMock->createTenant($tenant, true)->willReturn($tenant);
        $this->tenantConfigRepositoryMock->upsert(
            1,
            $tenant->config->siteName,
            $tenant->config->colorScheme,
            $tenant->config->primaryColor,
        )->shouldBeCalledOnce();

        $this->postHogServiceMock->capture(
            'tenant_trial_start',
            $userMock,
            [
                'tenant_id' => 1,
            ],
            [],
            [
                'tenant_trial_started' => date('c', strtotime('midnight')),
            ]
        )
            ->willReturn(true);

        $this->createNetworkTrial($tenant, $userMock)->shouldBe($tenant);
    }

    /**
     * @param int $id
     * @param MultiTenantConfig|null $tenantConfig
     * @return Tenant
     * @throws ReflectionException
     */
    private function generateTenantMock(
        int                    $id,
        MultiTenantConfig|null $tenantConfig = null
    ): Tenant {
        $tenant = $this->tenantMockFactory->newInstanceWithoutConstructor();
        $this->tenantMockFactory->getProperty('id')->setValue($tenant, $id);
        $this->tenantMockFactory->getProperty('config')->setValue($tenant, $tenantConfig);
        $this->tenantMockFactory->getProperty('trialStartTimestamp')->setValue($tenant, strtotime('midnight'));

        return $tenant;
    }

    private function generateTenantConfigMock(
        string                 $siteName,
        MultiTenantColorScheme $colorScheme,
        string                 $primaryColor
    ): MultiTenantConfig {
        $tenantConfig = $this->tenantConfigMockFactory->newInstanceWithoutConstructor();
        $this->tenantConfigMockFactory->getProperty('siteName')->setValue($tenantConfig, $siteName);
        $this->tenantConfigMockFactory->getProperty('colorScheme')->setValue($tenantConfig, $colorScheme);
        $this->tenantConfigMockFactory->getProperty('primaryColor')->setValue($tenantConfig, $primaryColor);

        return $tenantConfig;
    }
}
