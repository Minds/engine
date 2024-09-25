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
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use PhpSpec\ObjectBehavior;

class LogoGenerationHandlerSpec extends ObjectBehavior
{
    private $metadataExtractorMock;
    private $websiteIconExtractorMock;
    private $horizontalLogoExtractorMock;
    private $mobileSplashLogoExtractorMock;
    private $updateLogosDelegateMock;
    private $progressRepositoryMock;
    private $loggerMock;

    public function let(
        MetadataExtractor $metadataExtractor,
        WebsiteIconExtractor $websiteIconExtractor,
        HorizontalLogoExtractor $horizontalLogoExtractor,
        MobileSplashLogoExtractor $mobileSplashLogoExtractor,
        UpdateLogosDelegate $updateLogosDelegate,
        BootstrapProgressRepository $progressRepository,
        Logger $logger
    ) {
        $this->metadataExtractorMock = $metadataExtractor;
        $this->websiteIconExtractorMock = $websiteIconExtractor;
        $this->horizontalLogoExtractorMock = $horizontalLogoExtractor;
        $this->mobileSplashLogoExtractorMock = $mobileSplashLogoExtractor;
        $this->updateLogosDelegateMock = $updateLogosDelegate;
        $this->progressRepositoryMock = $progressRepository;
        $this->loggerMock = $logger;

        $this->beConstructedWith(
            $metadataExtractor,
            $websiteIconExtractor,
            $horizontalLogoExtractor,
            $mobileSplashLogoExtractor,
            $updateLogosDelegate,
            $progressRepository,
            $logger
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(LogoGenerationHandler::class);
    }

    public function it_should_handle_logo_generation()
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

        $this->updateLogosDelegateMock->onUpdate(
            $squareLogoBlob,
            $faviconBlob,
            $horizontalLogoBlob,
            $splashBlob
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, true)->shouldBeCalled();

        $this->handle($siteUrl);
    }

    public function it_should_handle_logo_generation_when_no_square_logo_blob_is_returned()
    {
        $siteUrl = 'https://example.minds.com';
        $faviconBlob = 'favicon-blob';

        $this->websiteIconExtractorMock->extract($siteUrl, 256)->willReturn(null);
        $this->websiteIconExtractorMock->extract($siteUrl, 32)->willReturn($faviconBlob);
        $this->horizontalLogoExtractorMock->extract(null)->shouldNotBeCalled();
        $this->mobileSplashLogoExtractorMock->extract(null)->shouldNotBeCalled();

        $this->updateLogosDelegateMock->onUpdate(
            null,
            $faviconBlob,
            null,
            null
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, true)->shouldBeCalled();

        $this->handle($siteUrl);
    }

    public function it_should_handle_errors_during_update_of_logos()
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

        $this->updateLogosDelegateMock->onUpdate(
            $squareLogoBlob,
            $faviconBlob,
            $horizontalLogoBlob,
            $splashBlob
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('Error'));

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::LOGO_STEP, false)->shouldBeCalled();

        $this->handle($siteUrl);
    }
}
