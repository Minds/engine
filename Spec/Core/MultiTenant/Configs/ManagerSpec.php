<?php

namespace Spec\Minds\Core\MultiTenant\Configs;

use Minds\Core\Config\Config;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Manager;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Configs\Repository;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\MultiTenant\Services\DomainService;
use Minds\Core\MultiTenant\Services\MultiTenantDataService;
use Minds\Exceptions\NotFoundException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class ManagerSpec extends ObjectBehavior
{
    private Collaborator $multiTenantDataService;
    private Collaborator $domainService;
    private Collaborator $repository;
    private Collaborator $logger;
    private Collaborator $config;

    public function let(
        MultiTenantDataService $multiTenantDataService,
        DomainService $domainService,
        Repository $repository,
        Logger $logger,
        Config $config
    ) {
        $this->beConstructedWith(
            $multiTenantDataService,
            $domainService,
            $repository,
            $logger,
            $config
        );
        $this->multiTenantDataService = $multiTenantDataService;
        $this->domainService = $domainService;
        $this->repository = $repository;
        $this->logger = $logger;
        $this->config = $config;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_call_to_get_configs(
        MultiTenantConfig $multiTenantConfig
    ) {
        $tenantId = 1234567890123456;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->repository->get($tenantId)
            ->shouldBeCalled()
            ->willReturn($multiTenantConfig);

        $this->getConfigs($tenantId)
            ->shouldBe($multiTenantConfig);
    }

    public function it_should_call_to_get_configs_and_return_null_when_not_found()
    {
        $tenantId = 1234567890123456;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->repository->get($tenantId)
            ->shouldBeCalled()
            ->willThrow(new NotFoundException());

        $this->logger->error(Argument::any())
            ->shouldNotBeCalled();

        $this->getConfigs($tenantId)
            ->shouldBe(null);
    }

    public function it_should_call_to_get_configs_and_return_null_when_generic_exception_thrown()
    {
        $tenantId = 1234567890123456;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->repository->get($tenantId)
            ->shouldBeCalled()
            ->willThrow(new \Exception());


        $this->logger->error(Argument::any())
            ->shouldBeCalled();

        $this->getConfigs($tenantId)
            ->shouldBe(null);
    }

    public function it_should_upsert_configs_and_invalidate_cache_on_success()
    {
        $tenantId = 1234567890123456;
        $siteName = 'Test site';
        $colorScheme = MultiTenantColorScheme::DARK;
        $primaryColor = '#000000';
        $customScript = '<script></script>';
        $domain = 'localhost';
        $result = true;
        $tenant = new Tenant($tenantId, $domain);
        $federationDisabled = true;
        $replyEmail = 'some@email.com';
        $nsfwEnabled = true;
        $boostEnabled = true;
        $lastCacheTimestamp = time();
        $customHomePageEnabled = true;
        $customHomePageDescription = 'Hello world';
        $walledGardenEnabled = true;
        $digestEmailEnabled = true;
        $welcomeEmailEnabled = true;
        $loggedInLandingPageIdWeb = 'newsfeed';
        $loggedInLandingPageIdMobile = 'explore';
        $isNonProfit = true;

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->repository->upsert(
            $tenantId,
            $siteName,
            $colorScheme,
            $primaryColor,
            $customScript,
            $federationDisabled,
            $replyEmail,
            $nsfwEnabled,
            $boostEnabled,
            $customHomePageEnabled,
            $customHomePageDescription,
            $walledGardenEnabled,
            $digestEmailEnabled,
            $welcomeEmailEnabled,
            $loggedInLandingPageIdWeb,
            $loggedInLandingPageIdMobile,
            $isNonProfit,
            $lastCacheTimestamp
        )
            ->shouldBeCalled()
            ->willReturn($result);

        $this->multiTenantDataService->getTenantFromId($tenantId)
            ->shouldBeCalled()
            ->willReturn($tenant);

        $this->domainService->invalidateGlobalTenantCache($tenant)
            ->shouldBeCalled();

        $this->upsertConfigs(
            $siteName,
            $colorScheme,
            $primaryColor,
            $customScript,
            $federationDisabled,
            $replyEmail,
            $nsfwEnabled,
            $boostEnabled,
            $customHomePageEnabled,
            $customHomePageDescription,
            $walledGardenEnabled,
            $digestEmailEnabled,
            $welcomeEmailEnabled,
            $loggedInLandingPageIdWeb,
            $loggedInLandingPageIdMobile,
            $isNonProfit,
            $lastCacheTimestamp
        )->shouldBe($result);
    }

    public function it_should_try_to_upsert_configs_but_NOT_invalidate_cache_on_failure()
    {
        $tenantId = 1234567890123456;
        $siteName = 'Test site';
        $colorScheme = MultiTenantColorScheme::DARK;
        $primaryColor = '#000000';
        $customScript = '<script></script>';
        $result = false;
        $federationDisabled = true;
        $replyEmail = 'some@email.com';
        $nsfwEnabled = true;
        $boostEnabled = true;
        $customHomePageEnabled = true;
        $customHomePageDescription = 'Hello world';
        $walledGardenEnabled = true;
        $digestEmailEnabled = true;
        $welcomeEmailEnabled = true;
        $loggedInLandingPageIdWeb = 'newsfeed';
        $loggedInLandingPageIdMobile = 'explore';
        $welcomeEmailEnabled = true;
        $isNonProfit = true;
        $lastCacheTimestamp = time();

        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn($tenantId);

        $this->repository->upsert(
            tenantId: $tenantId,
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
            customScript: $customScript,
            federationDisabled: $federationDisabled,
            replyEmail: $replyEmail,
            nsfwEnabled: $nsfwEnabled,
            boostEnabled: $boostEnabled,
            customHomePageEnabled: $customHomePageEnabled,
            customHomePageDescription: $customHomePageDescription,
            walledGardenEnabled: $walledGardenEnabled,
            digestEmailEnabled: $digestEmailEnabled,
            welcomeEmailEnabled: $welcomeEmailEnabled,
            loggedInLandingPageIdWeb: $loggedInLandingPageIdWeb,
            loggedInLandingPageIdMobile: $loggedInLandingPageIdMobile,
            isNonProfit: $isNonProfit,
            lastCacheTimestamp: $lastCacheTimestamp
        )
            ->shouldBeCalled()
            ->willReturn($result);

        $this->multiTenantDataService->getTenantFromId(Argument::any())
            ->shouldNotBeCalled();

        $this->domainService->invalidateGlobalTenantCache(Argument::any())
            ->shouldNotBeCalled();

        $this->upsertConfigs(
            $siteName,
            $colorScheme,
            $primaryColor,
            $customScript,
            $federationDisabled,
            $replyEmail,
            $nsfwEnabled,
            $boostEnabled,
            $customHomePageEnabled,
            $customHomePageDescription,
            $walledGardenEnabled,
            $digestEmailEnabled,
            $welcomeEmailEnabled,
            $loggedInLandingPageIdWeb,
            $loggedInLandingPageIdMobile,
            $isNonProfit,
            $lastCacheTimestamp
        )->shouldBe($result);
    }
}
