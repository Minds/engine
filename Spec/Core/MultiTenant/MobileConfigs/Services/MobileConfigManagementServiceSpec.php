<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Services;

use Google\Service\TrafficDirectorService\NullMatch;
use Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class MobileConfigManagementServiceSpec extends ObjectBehavior
{
    private Collaborator $mobileConfigRepositoryMock;
    private Collaborator $mobilePreviewHandlerMock;

    public function let(
        MobileConfigRepository $mobileConfigRepository,
        MobilePreviewHandler   $mobilePreviewHandler,
    ): void {
        $this->mobileConfigRepositoryMock = $mobileConfigRepository;
        $this->mobilePreviewHandlerMock = $mobilePreviewHandler;

        $this->beConstructedWith(
            $this->mobileConfigRepositoryMock,
            $this->mobilePreviewHandlerMock,
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(MobileConfigManagementService::class);
    }

    /**
     * @return void
     */
    public function it_should_process_mobile_preview_webhook(): void
    {
        $this->mobileConfigRepositoryMock->storeMobileConfig(
            1,
            null,
            null,
            MobilePreviewStatusEnum::READY,
            "5.0.0",
            null,
            null
        )
            ->shouldBeCalledOnce();

        $this->processMobilePreviewWebhook(
            1,
            '5.0.0',
            'success'
        );
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_NO_config_exists_and_splash_screen_type_provided(): void
    {
        $splashScreenType = MobileSplashScreenTypeEnum::CONTAIN;
        $welcomeScreenLogoType = null;
        $previewStatus = null;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
            null
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe($splashScreenType);
        $response->welcomeScreenLogoType->shouldBe(MobileWelcomeScreenLogoTypeEnum::SQUARE);
        $response->previewStatus->shouldBe(MobilePreviewStatusEnum::NO_PREVIEW);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_NO_config_exists_and_welcome_screen_logo_type_provided(): void
    {
        $splashScreenType = null;
        $welcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::HORIZONTAL;
        $previewStatus = null;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
            null
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
        );

        $response->splashScreenType->shouldBe(MobileSplashScreenTypeEnum::CONTAIN);
        $response->welcomeScreenLogoType->shouldBe($welcomeScreenLogoType);
        $response->previewStatus->shouldBe(MobilePreviewStatusEnum::NO_PREVIEW);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_NO_config_exists_and_pending_preview_status_provided(): void
    {
        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = MobilePreviewStatusEnum::PENDING;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
            null
        )
            ->shouldBeCalledOnce();

        $this->mobilePreviewHandlerMock->triggerPipeline()
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe(MobileSplashScreenTypeEnum::CONTAIN);
        $response->welcomeScreenLogoType->shouldBe(MobileWelcomeScreenLogoTypeEnum::SQUARE);
        $response->previewStatus->shouldBe($previewStatus);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_NO_config_exists_and_NON_pending_preview_status_provided(): void
    {
        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = MobilePreviewStatusEnum::READY;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            null,
            null,
            null,
            null
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe(MobileSplashScreenTypeEnum::CONTAIN);
        $response->welcomeScreenLogoType->shouldBe(MobileWelcomeScreenLogoTypeEnum::SQUARE);
        $response->previewStatus->shouldBe(MobilePreviewStatusEnum::NO_PREVIEW);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_NO_config_exists_and_app_tracking_message_enabled_value_provided(): void
    {
        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = null;
        $appTrackingMessageEnabled = true;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            null,
            null,
            $appTrackingMessageEnabled,
            null
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            $appTrackingMessageEnabled,
            null,
        );

        $response->splashScreenType->shouldBe(MobileSplashScreenTypeEnum::CONTAIN);
        $response->welcomeScreenLogoType->shouldBe(MobileWelcomeScreenLogoTypeEnum::SQUARE);
        $response->previewStatus->shouldBe(MobilePreviewStatusEnum::NO_PREVIEW);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_NO_config_exists_and_app_tracking_message_provided(): void
    {
        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = null;
        $appTrackingMessageEnabled = true;
        $appTrackingMessage = 'test';

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willThrow(NoMobileConfigFoundException::class);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            null,
            null,
            $appTrackingMessageEnabled,
            $appTrackingMessage
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            $appTrackingMessageEnabled,
            $appTrackingMessage,
        );

        $response->splashScreenType->shouldBe(MobileSplashScreenTypeEnum::CONTAIN);
        $response->welcomeScreenLogoType->shouldBe(MobileWelcomeScreenLogoTypeEnum::SQUARE);
        $response->previewStatus->shouldBe(MobilePreviewStatusEnum::NO_PREVIEW);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_config_exists_and_splash_screen_type_provided(): void
    {
        $expectedMobileConfig = new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: MobileSplashScreenTypeEnum::COVER,
            welcomeScreenLogoType: MobileWelcomeScreenLogoTypeEnum::HORIZONTAL,
            previewStatus: MobilePreviewStatusEnum::ERROR,
        );

        $splashScreenType = MobileSplashScreenTypeEnum::CONTAIN;
        $welcomeScreenLogoType = null;
        $previewStatus = null;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willReturn($expectedMobileConfig);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
            MobileConfig::DEFAULT_APP_TRACKING_MESSAGE
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe($splashScreenType);
        $response->welcomeScreenLogoType->shouldBe($expectedMobileConfig->welcomeScreenLogoType);
        $response->previewStatus->shouldBe($expectedMobileConfig->previewStatus);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_config_exists_and_welcome_screen_logo_type_provided(): void
    {
        $expectedMobileConfig = new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: MobileSplashScreenTypeEnum::COVER,
            welcomeScreenLogoType: MobileWelcomeScreenLogoTypeEnum::HORIZONTAL,
            previewStatus: MobilePreviewStatusEnum::ERROR,
        );

        $splashScreenType = null;
        $welcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::SQUARE;
        $previewStatus = null;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willReturn($expectedMobileConfig);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
            MobileConfig::DEFAULT_APP_TRACKING_MESSAGE
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe($expectedMobileConfig->splashScreenType);
        $response->welcomeScreenLogoType->shouldBe($welcomeScreenLogoType);
        $response->previewStatus->shouldBe($expectedMobileConfig->previewStatus);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_config_exists_and_pending_preview_status_provided(): void
    {
        $expectedMobileConfig = new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: MobileSplashScreenTypeEnum::COVER,
            welcomeScreenLogoType: MobileWelcomeScreenLogoTypeEnum::HORIZONTAL,
            previewStatus: MobilePreviewStatusEnum::ERROR,
        );

        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = MobilePreviewStatusEnum::PENDING;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willReturn($expectedMobileConfig);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null,
            MobileConfig::DEFAULT_APP_TRACKING_MESSAGE
        )
            ->shouldBeCalledOnce();

        $this->mobilePreviewHandlerMock->triggerPipeline()
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe($expectedMobileConfig->splashScreenType);
        $response->welcomeScreenLogoType->shouldBe($expectedMobileConfig->welcomeScreenLogoType);
        $response->previewStatus->shouldBe($previewStatus);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_config_exists_and_NON_pending_preview_status_provided(): void
    {
        $expectedMobileConfig = new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: MobileSplashScreenTypeEnum::COVER,
            welcomeScreenLogoType: MobileWelcomeScreenLogoTypeEnum::HORIZONTAL,
            previewStatus: MobilePreviewStatusEnum::ERROR,
        );

        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = MobilePreviewStatusEnum::READY;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willReturn($expectedMobileConfig);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            null,
            null,
            null,
            MobileConfig::DEFAULT_APP_TRACKING_MESSAGE
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            null,
            null
        );

        $response->splashScreenType->shouldBe($expectedMobileConfig->splashScreenType);
        $response->welcomeScreenLogoType->shouldBe($expectedMobileConfig->welcomeScreenLogoType);
        $response->previewStatus->shouldBe($expectedMobileConfig->previewStatus);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_config_exists_and_tracking_message_enabled_value_is_provided(): void
    {
        $expectedMobileConfig = new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: MobileSplashScreenTypeEnum::COVER,
            welcomeScreenLogoType: MobileWelcomeScreenLogoTypeEnum::HORIZONTAL,
            previewStatus: MobilePreviewStatusEnum::ERROR,
            appTrackingMessageEnabled: true
        );

        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = MobilePreviewStatusEnum::READY;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willReturn($expectedMobileConfig);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            null,
            null,
            true,
            MobileConfig::DEFAULT_APP_TRACKING_MESSAGE
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            true,
            null
        );

        $response->splashScreenType->shouldBe($expectedMobileConfig->splashScreenType);
        $response->welcomeScreenLogoType->shouldBe($expectedMobileConfig->welcomeScreenLogoType);
        $response->previewStatus->shouldBe($expectedMobileConfig->previewStatus);
    }

    /**
     * @return void
     */
    public function it_should_store_mobile_config_when_config_exists_and_tracking_message_is_provided(): void
    {
        $appTrackingMessage = 'test';
        $expectedMobileConfig = new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: MobileSplashScreenTypeEnum::COVER,
            welcomeScreenLogoType: MobileWelcomeScreenLogoTypeEnum::HORIZONTAL,
            previewStatus: MobilePreviewStatusEnum::ERROR,
            appTrackingMessageEnabled: true,
            appTrackingMessage: $appTrackingMessage
        );

        $splashScreenType = null;
        $welcomeScreenLogoType = null;
        $previewStatus = MobilePreviewStatusEnum::READY;

        $this->mobileConfigRepositoryMock->getMobileConfig()
            ->shouldBeCalledOnce()
            ->willReturn($expectedMobileConfig);

        $this->mobileConfigRepositoryMock->storeMobileConfig(
            null,
            $splashScreenType,
            $welcomeScreenLogoType,
            null,
            null,
            true,
            $appTrackingMessage
        )
            ->shouldBeCalledOnce();

        /**
         * @var MobileConfig $response
         */
        $response = $this->storeMobileConfig(
            $splashScreenType,
            $welcomeScreenLogoType,
            $previewStatus,
            true,
            $appTrackingMessage
        );

        $response->splashScreenType->shouldBe($expectedMobileConfig->splashScreenType);
        $response->welcomeScreenLogoType->shouldBe($expectedMobileConfig->welcomeScreenLogoType);
        $response->previewStatus->shouldBe($expectedMobileConfig->previewStatus);
    }
}
