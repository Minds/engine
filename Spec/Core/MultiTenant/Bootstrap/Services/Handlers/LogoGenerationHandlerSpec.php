<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\LogoGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\WebsiteIconExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateLogosDelegate;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserAvatarDelegate;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Entities\User;
use Minds\Helpers\Image as ImageHelpers;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LogoGenerationHandlerSpec extends ObjectBehavior
{
    private $metadataExtractorMock;
    private $websiteIconExtractorMock;
    private $horizontalLogoExtractorMock;
    private $mobileSplashLogoExtractorMock;
    private $updateLogosDelegateMock;
    private $updateUserAvatarDelegateMock;
    private $progressRepositoryMock;
    private $imageHelpersMock;
    private $loggerMock;

    public function let(
        MetadataExtractor $metadataExtractor,
        WebsiteIconExtractor $websiteIconExtractor,
        HorizontalLogoExtractor $horizontalLogoExtractor,
        MobileSplashLogoExtractor $mobileSplashLogoExtractor,
        UpdateLogosDelegate $updateLogosDelegate,
        UpdateUserAvatarDelegate $updateUserAvatarDelegate,
        BootstrapProgressRepository $progressRepository,
        ImageHelpers $imageHelpers,
        Logger $logger
    ) {
        $this->metadataExtractorMock = $metadataExtractor;
        $this->websiteIconExtractorMock = $websiteIconExtractor;
        $this->horizontalLogoExtractorMock = $horizontalLogoExtractor;
        $this->mobileSplashLogoExtractorMock = $mobileSplashLogoExtractor;
        $this->updateLogosDelegateMock = $updateLogosDelegate;
        $this->updateUserAvatarDelegateMock = $updateUserAvatarDelegate;
        $this->progressRepositoryMock = $progressRepository;
        $this->imageHelpersMock = $imageHelpers;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $metadataExtractor,
            $websiteIconExtractor,
            $horizontalLogoExtractor,
            $mobileSplashLogoExtractor,
            $updateLogosDelegate,
            $updateUserAvatarDelegate,
            $progressRepository,
            $imageHelpers,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LogoGenerationHandler::class);
    }

    public function it_should_handle_logo_generation(User $rootUserMock)
    {
        $siteUrl = 'https://example.minds.com';
        $squareLogoBlob = 'square-logo-blob';
        $faviconBlob = 'favicon-blob';
        $horizontalLogoBlob = 'horizontal-logo-blob';
        $splashBlob = 'splash-blob';

        $this->websiteIconExtractorMock->extract($siteUrl, 256)->willReturn($squareLogoBlob);
        $this->websiteIconExtractorMock->extract($siteUrl, 32)->willReturn($faviconBlob);
        $this->horizontalLogoExtractorMock->extract($squareLogoBlob)->willReturn($horizontalLogoBlob);
        $this->mobileSplashLogoExtractorMock->extract($squareLogoBlob)->willReturn($splashBlob);

        $this->imageHelpersMock->isValidImage(Argument::any())->willReturn(true);

        $this->updateLogosDelegateMock->onUpdate(
            $squareLogoBlob,
            $faviconBlob,
            $horizontalLogoBlob,
            $splashBlob
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateUserAvatarDelegateMock->onUpdate(
            $rootUserMock,
            $squareLogoBlob
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, true)->shouldBeCalled();

        $this->handle($siteUrl, $rootUserMock);
    }

    public function it_should_handle_logo_generation_when_no_square_logo_blob_is_returned(User $rootUserMock)
    {
        $siteUrl = 'https://example.minds.com';
        $faviconBlob = 'favicon-blob';

        $this->websiteIconExtractorMock->extract($siteUrl, 256)->willReturn(null);

        $this->imageHelpersMock->isValidImage(Argument::any())->willReturn(true);

        $this->websiteIconExtractorMock->extract($siteUrl, 32)->willReturn($faviconBlob);
        $this->horizontalLogoExtractorMock->extract(null)->shouldNotBeCalled();
        $this->mobileSplashLogoExtractorMock->extract(null)->shouldNotBeCalled();

        $this->updateLogosDelegateMock->onUpdate(
            null,
            $faviconBlob,
            null,
            null
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, false)->shouldBeCalled();

        $this->handle($siteUrl, $rootUserMock);
    }

    public function it_should_handle_logo_generation_when_invalid_square_logo_blob_is_returned(User $rootUserMock)
    {
        $siteUrl = 'https://example.minds.com';
        $faviconBlob = 'favicon-blob';

        $this->websiteIconExtractorMock->extract($siteUrl, 256)->willReturn('square-logo-blob');
        $this->websiteIconExtractorMock->extract($siteUrl, 32)->willReturn($faviconBlob);

        $this->imageHelpersMock->isValidImage('square-logo-blob')->willReturn(false);
        $this->imageHelpersMock->isValidImage($faviconBlob)->willReturn(true);

        $this->horizontalLogoExtractorMock->extract(null)->shouldNotBeCalled();
        $this->mobileSplashLogoExtractorMock->extract(null)->shouldNotBeCalled();

        $this->updateLogosDelegateMock->onUpdate(
            null,
            $faviconBlob,
            null,
            null
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, false)->shouldBeCalled();

        $this->handle($siteUrl, $rootUserMock);
    }

    public function it_should_handle_errors_during_update_of_logos(User $rootUserMock)
    {
        $siteUrl = 'https://example.minds.com';
        $squareLogoBlob = 'square-logo-blob';
        $faviconBlob = 'favicon-blob';
        $horizontalLogoBlob = 'horizontal-logo-blob';
        $splashBlob = 'splash-blob';

        $this->websiteIconExtractorMock->extract($siteUrl, 256)->willReturn($squareLogoBlob);
        $this->websiteIconExtractorMock->extract($siteUrl, 32)->willReturn($faviconBlob);
        $this->horizontalLogoExtractorMock->extract($squareLogoBlob)->willReturn($horizontalLogoBlob);
        $this->mobileSplashLogoExtractorMock->extract($squareLogoBlob)->willReturn($splashBlob);

        $this->imageHelpersMock->isValidImage(Argument::any())->willReturn(true);

        $this->updateLogosDelegateMock->onUpdate(
            $squareLogoBlob,
            $faviconBlob,
            $horizontalLogoBlob,
            $splashBlob
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('Error'));

        $this->updateUserAvatarDelegateMock->onUpdate(
            $rootUserMock,
            $squareLogoBlob
        )
            ->shouldNotBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, false)->shouldBeCalled();

        $this->handle($siteUrl, $rootUserMock);
    }


    public function it_should_handle_errors_during_user_avatar_update(User $rootUserMock)
    {
        $siteUrl = 'https://example.minds.com';
        $squareLogoBlob = 'square-logo-blob';
        $faviconBlob = 'favicon-blob';
        $horizontalLogoBlob = 'horizontal-logo-blob';
        $splashBlob = 'splash-blob';

        $this->websiteIconExtractorMock->extract($siteUrl, 256)->willReturn($squareLogoBlob);
        $this->websiteIconExtractorMock->extract($siteUrl, 32)->willReturn($faviconBlob);
        $this->horizontalLogoExtractorMock->extract($squareLogoBlob)->willReturn($horizontalLogoBlob);
        $this->mobileSplashLogoExtractorMock->extract($squareLogoBlob)->willReturn($splashBlob);

        $this->imageHelpersMock->isValidImage(Argument::any())->willReturn(true);

        $this->updateLogosDelegateMock->onUpdate(
            $squareLogoBlob,
            $faviconBlob,
            $horizontalLogoBlob,
            $splashBlob
        )
            ->shouldBeCalled()
            ->willReturn(true);

        $this->updateUserAvatarDelegateMock->onUpdate(
            $rootUserMock,
            $squareLogoBlob
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('Error'));

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, false)->shouldBeCalled();

        $this->handle($siteUrl, $rootUserMock);
    }
}
