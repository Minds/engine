<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateLogosDelegate;
use Minds\Core\MultiTenant\Configs\Image\Manager as ConfigImageManager;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigAssetsService;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileConfigImageTypeEnum;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class UpdateLogosDelegateSpec extends ObjectBehavior
{
    private Collaborator $configImageManagerMock;
    private Collaborator $mobileConfigAssetsServiceMock;
    private Collaborator $horizontalLogoExtractorMock;
    private Collaborator $mobileSplashLogoExtractorMock;
    private Collaborator $loggerMock;

    public function let(
        ConfigImageManager $configImageManagerMock,
        MobileConfigAssetsService $mobileConfigAssetsServiceMock,
        HorizontalLogoExtractor $horizontalLogoExtractorMock,
        MobileSplashLogoExtractor $mobileSplashLogoExtractorMock,
        Logger $loggerMock
    ) {
        $this->configImageManagerMock = $configImageManagerMock;
        $this->mobileConfigAssetsServiceMock = $mobileConfigAssetsServiceMock;
        $this->horizontalLogoExtractorMock = $horizontalLogoExtractorMock;
        $this->mobileSplashLogoExtractorMock = $mobileSplashLogoExtractorMock;
        $this->loggerMock = $loggerMock;

        $this->beConstructedWith(
            $configImageManagerMock,
            $mobileConfigAssetsServiceMock,
            $horizontalLogoExtractorMock,
            $mobileSplashLogoExtractorMock,
            $loggerMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdateLogosDelegate::class);
    }

    public function it_should_update_square_logo()
    {
        $squareLogoBlob = 'square-logo-blob';

        $this->configImageManagerMock->uploadBlob($squareLogoBlob, MultiTenantConfigImageType::SQUARE_LOGO)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mobileConfigAssetsServiceMock->uploadBlob($squareLogoBlob, MobileConfigImageTypeEnum::ICON)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mobileConfigAssetsServiceMock->uploadBlob($squareLogoBlob, MobileConfigImageTypeEnum::SQUARE_LOGO)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->loggerMock->info("Uploaded web square logo")->shouldBeCalled();
        $this->loggerMock->info("Uploaded mobile icon")->shouldBeCalled();
        $this->loggerMock->info("Uploaded mobile square logo")->shouldBeCalled();
        $this->loggerMock->info("Done uploading logos")->shouldBeCalled();

        $this->onUpdate($squareLogoBlob);
    }

    public function it_should_handle_failure_to_update_square_logo()
    {
        $squareLogoBlob = 'square-logo-blob';

        $this->configImageManagerMock->uploadBlob($squareLogoBlob, MultiTenantConfigImageType::SQUARE_LOGO)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->mobileConfigAssetsServiceMock->uploadBlob($squareLogoBlob, MobileConfigImageTypeEnum::ICON)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->mobileConfigAssetsServiceMock->uploadBlob($squareLogoBlob, MobileConfigImageTypeEnum::SQUARE_LOGO)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->loggerMock->error("Failed to upload web square logo: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload mobile icon: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload mobile square logo: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload all logos, some may have been saved.")->shouldBeCalled();

        $this->onUpdate($squareLogoBlob);
    }

    public function it_should_update_horizontal_logo()
    {
        $horizontalLogoBlob = 'horizontal-logo-blob';

        $this->configImageManagerMock->uploadBlob($horizontalLogoBlob, MultiTenantConfigImageType::HORIZONTAL_LOGO)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->mobileConfigAssetsServiceMock->uploadBlob($horizontalLogoBlob, MobileConfigImageTypeEnum::HORIZONTAL_LOGO)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->loggerMock->info("Uploaded web horizontal logo")->shouldBeCalled();
        $this->loggerMock->info("Uploaded mobile horizontal logo")->shouldBeCalled();
        $this->loggerMock->info("Done uploading logos")->shouldBeCalled();

        $this->onUpdate(null, null, $horizontalLogoBlob);
    }

    public function it_should_handle_failure_to_update_horizontal_logo()
    {
        $horizontalLogoBlob = 'horizontal-logo-blob';

        $this->configImageManagerMock->uploadBlob($horizontalLogoBlob, MultiTenantConfigImageType::HORIZONTAL_LOGO)
            ->shouldBeCalled()
            ->willReturn(false);
            
        $this->mobileConfigAssetsServiceMock->uploadBlob($horizontalLogoBlob, MobileConfigImageTypeEnum::HORIZONTAL_LOGO)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->loggerMock->error("Failed to upload web horizontal logo: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload mobile horizontal logo: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload all logos, some may have been saved.")->shouldBeCalled();

        $this->onUpdate(null, null, $horizontalLogoBlob);
    }

    public function it_should_update_favicon()
    {
        $faviconBlob = 'favicon-blob';

        $this->configImageManagerMock->uploadBlob($faviconBlob, MultiTenantConfigImageType::FAVICON)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->loggerMock->info("Uploaded web favicon")->shouldBeCalled();
        $this->loggerMock->info("Done uploading logos")->shouldBeCalled();

        $this->onUpdate(null, $faviconBlob);
    }

    public function it_should_handle_failure_to_update_favicon()
    {
        $faviconBlob = 'favicon-blob';

        $this->configImageManagerMock->uploadBlob($faviconBlob, MultiTenantConfigImageType::FAVICON)
            ->shouldBeCalled()
            ->willReturn(false);
            
        $this->loggerMock->error("Failed to upload web favicon: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload all logos, some may have been saved.")->shouldBeCalled();

        $this->onUpdate(null, $faviconBlob);
    }

    public function it_should_update_mobile_splash()
    {
        $splashBlob = 'splash-blob';

        $this->mobileConfigAssetsServiceMock->uploadBlob($splashBlob, MobileConfigImageTypeEnum::SPLASH)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->loggerMock->info("Uploaded mobile splash")->shouldBeCalled();
        $this->loggerMock->info("Done uploading logos")->shouldBeCalled();

        $this->onUpdate(null, null, null, $splashBlob);
    }

    public function it_should_handle_failure_to_update_mobile_splash()
    {
        $splashBlob = 'splash-blob';

        $this->mobileConfigAssetsServiceMock->uploadBlob($splashBlob, MobileConfigImageTypeEnum::SPLASH)
            ->shouldBeCalled()
            ->willReturn(false);
            
        $this->loggerMock->error("Failed to upload mobile splash: Upload failed")->shouldBeCalled();
        $this->loggerMock->error("Failed to upload all logos, some may have been saved.")->shouldBeCalled();

        $this->onUpdate(null, null, null, $splashBlob);
    }
}
