<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Services;

use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileAppPreviewQRCodeService;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class MobileAppPreviewQRCodeServiceSpec extends ObjectBehavior
{
    private Collaborator $mobileConfigRepositoryMock;
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $configMock;

    public function let(
        MobileConfigRepository $mobileConfigRepository,
        MultiTenantBootService $multiTenantBootService,
        Config $config
    ): void {
        $this->mobileConfigRepositoryMock = $mobileConfigRepository;
        $this->multiTenantBootServiceMock = $multiTenantBootService;
        $this->configMock = $config;

        $this->beConstructedWith(
            $mobileConfigRepository,
            $multiTenantBootService,
            $config
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(MobileAppPreviewQRCodeService::class);
    }

    public function it_should_get_blob(
        MobileConfig $mobileConfig
    ): void {
        $tenantId = 123;
        $versionlessPreviewQRCode = 'qrUrl';

        $mobileConfig->getVersionlessPreviewQRCode()
            ->shouldBeCalled()
            ->willReturn($versionlessPreviewQRCode);

        $this->multiTenantBootServiceMock->bootFromTenantId($tenantId)
            ->shouldBeCalled();

        $this->mobileConfigRepositoryMock->getMobileConfig($tenantId)
            ->shouldBeCalled()
            ->willReturn($mobileConfig);

        $this->getBlob($tenantId)->shouldContain('PNG');
    }

    public function it_should_not_get_blob_when_there_is_no_qr_url(
        MobileConfig $mobileConfig
    ): void {
        $tenantId = 123;

        $mobileConfig->getVersionlessPreviewQRCode()
            ->shouldBeCalled()
            ->willReturn('');

        $this->multiTenantBootServiceMock->bootFromTenantId($tenantId)
            ->shouldBeCalled();

        $this->mobileConfigRepositoryMock->getMobileConfig($tenantId)
            ->shouldBeCalled()
            ->willReturn($mobileConfig);

        $this->getBlob($tenantId)->shouldBe('');
    }
}
