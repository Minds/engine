<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Services\Handlers\ConfigGenerationHandler;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ThemeExtractor;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateConfigDelegate;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use PhpSpec\ObjectBehavior;

class ConfigGenerationHandlerSpec extends ObjectBehavior
{
    private $themeExtractorMock;
    private $updateConfigDelegateMock;
    private $progressRepositoryMock;
    private $loggerMock;

    public function let(
        ThemeExtractor $themeExtractor,
        UpdateConfigDelegate $updateConfigDelegate,
        BootstrapProgressRepository $progressRepository,
        Logger $logger
    ) {
        $this->themeExtractorMock = $themeExtractor;
        $this->updateConfigDelegateMock = $updateConfigDelegate;
        $this->progressRepositoryMock = $progressRepository;
        $this->loggerMock = $logger;

        $this->beConstructedWith($themeExtractor, $updateConfigDelegate, $progressRepository, $logger);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ConfigGenerationHandler::class);
    }

    public function it_should_handle_config_generation_with_screenshot()
    {
        $screenshotBlob = 'fake-screenshot-blob';
        $description = 'Description';
        $siteName = 'Name';
        $theme = ['theme' => 'light', 'color' => '#FF0000'];

        $this->themeExtractorMock->extract($screenshotBlob)->willReturn($theme);

        $this->updateConfigDelegateMock->onUpdate(
            $siteName,
            MultiTenantColorScheme::LIGHT,
            '#FF0000',
            $description
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::TENANT_CONFIG_STEP, true)->shouldBeCalled();

        $this->handle($screenshotBlob, $description, $siteName);
    }

    public function it_should_handle_config_generation_without_screenshot()
    {
        $description = 'Description';
        $siteName = 'Name';

        $this->updateConfigDelegateMock->onUpdate(
            $siteName,
            null,
            null,
            $description
        )->shouldBeCalled();

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::TENANT_CONFIG_STEP, true)->shouldBeCalled();

        $this->handle(null, $description, $siteName);
    }

    public function it_should_handle_errors_during_config_generation()
    {
        $screenshotBlob = 'fake-screenshot-blob';
        $description = 'Description';
        $siteName = 'Name';

        $this->themeExtractorMock->extract($screenshotBlob)->willThrow(new \Exception('Theme extraction failed'));

        $this->progressRepositoryMock->updateProgress(BootstrapStepEnum::TENANT_CONFIG_STEP, false)->shouldBeCalled();

        $this->handle($screenshotBlob, $description, $siteName);
    }
}
