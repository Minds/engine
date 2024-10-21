<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Services;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Services\ProductionAppVersionService;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class ProductionAppVersionServiceSpec extends ObjectBehavior
{
    private $repositoryMock;
    private $loggerMock;

    public function let(MobileConfigRepository $repository, Logger $logger)
    {
        $this->repositoryMock = $repository;
        $this->loggerMock = $logger;
        $this->beConstructedWith($repository, $logger);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(ProductionAppVersionService::class);
    }

    public function it_should_set_production_mobile_app_version(): void
    {
        $tenantId = 1;
        $productionAppVersion = '1.0.0';

        $this->repositoryMock->storeMobileConfig(
            $tenantId,
            Argument::cetera()
        )->shouldBeCalled();

        $this->setProductionMobileAppVersion($tenantId, $productionAppVersion)
            ->shouldReturn(true);
    }

    public function it_should_handle_exceptions_when_setting_production_mobile_app_version(): void
    {
        $tenantId = 1;
        $productionAppVersion = '1.0.0';

        $this->repositoryMock->storeMobileConfig(
            $tenantId,
            Argument::cetera()
        )->willThrow(new \Exception());

        $this->loggerMock->error(Argument::type('string'))
            ->shouldBeCalled();

        $this->setProductionMobileAppVersion($tenantId, $productionAppVersion)
            ->shouldReturn(false);
    }

    public function it_should_clear_production_mobile_app_version_for_all_tenants(): void
    {
        $this->repositoryMock->clearAllProductionMobileAppVersions()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->clearForAllTenants()
            ->shouldReturn(true);
    }

    public function it_should_handle_exceptions_when_clearing_production_mobile_app_version_for_all_tenants(): void
    {
        $this->repositoryMock->clearAllProductionMobileAppVersions()
            ->willThrow(new \Exception());

        $this->loggerMock->error(Argument::type('string'))
            ->shouldBeCalled();

        $this->clearForAllTenants()
            ->shouldReturn(false);
    }
}
