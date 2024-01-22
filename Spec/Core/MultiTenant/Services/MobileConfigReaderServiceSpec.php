<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class MobileConfigReaderServiceSpec extends ObjectBehavior
{
    private Collaborator $mobileConfigRepositoryMock;
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $configMock;

    public function let(
        MobileConfigRepository $mobileConfigRepository,
        MultiTenantBootService $multiTenantBootService,
        Config                 $config
    ): void {
        $this->mobileConfigRepositoryMock = $mobileConfigRepository;
        $this->multiTenantBootServiceMock = $multiTenantBootService;
        $this->configMock = $config;

        $this->beConstructedWith(
            $this->mobileConfigRepositoryMock,
            $this->multiTenantBootServiceMock,
            $this->configMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MobileConfigReaderService::class);
    }

    // public function it_should_get_app_ready_mobile_config(): void
    // {
    //     $this->multiTenantBootServiceMock->bootFromTenantId(1)
    //         ->shouldBeCalledOnce();
    //     $this->multiTenantBootServiceMock->getTenant()
    //         ->shouldBeCalledOnce();
    //     $this->mobileConfigRepositoryMock->getMobileConfig(1)
    //         ->shouldBeCalledOnce();
    //     $this->configMock->get('site_url')
    //         ->shouldBeCalledOnce()
    //         ->willReturn();
    //     $this->multiTenantBootServiceMock->resetRootConfigs()
    //         ->shouldBeCalledOnce();
    //     $this->getAppReadyMobileConfig(1)
    //         ->shouldBeAnInstanceOf(AppReadyMobileConfig::class);
    // }
}
