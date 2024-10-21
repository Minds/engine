<?php
declare(strict_types=1);


namespace Spec\Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Minds\Core\MultiTenant\MobileConfigs\Controllers\MobileConfigManagementController;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\MobileConfigs\Services\ProductionAppVersionService;
use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Spec\Minds\Common\Traits\TenantFactoryMockBuilder;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class MobileConfigManagementControllerSpec extends ObjectBehavior
{
    use TenantFactoryMockBuilder;

    /** @var MobileConfigManagementService */
    protected $mobileConfigManagementServiceMock;

    /** @var ProductionAppVersionService */
    protected $productionAppVersionServiceMock;

    /** @var GitlabPipelineJwtTokenValidator */
    protected $gitlabPipelineJwtTokenValidatorMock;

    public function let(
        MobileConfigManagementService $mobileConfigManagementServiceMock,
        ProductionAppVersionService $productionAppVersionServiceMock,
        GitlabPipelineJwtTokenValidator $gitlabPipelineJwtTokenValidatorMock
    ) {
        $this->mobileConfigManagementServiceMock = $mobileConfigManagementServiceMock;
        $this->productionAppVersionServiceMock = $productionAppVersionServiceMock;
        $this->gitlabPipelineJwtTokenValidatorMock = $gitlabPipelineJwtTokenValidatorMock;

        $this->beConstructedWith(
            $mobileConfigManagementServiceMock,
            $productionAppVersionServiceMock,
            $gitlabPipelineJwtTokenValidatorMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(MobileConfigManagementController::class);
    }

    public function it_should_store_mobile_config(User $loggedInUser)
    {
        $mobileSplashScreenType = MobileSplashScreenTypeEnum::CONTAIN;
        $mobileWelcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::SQUARE;
        $mobilePreviewStatus = MobilePreviewStatusEnum::READY;
        $appTrackingMessageEnabled = true;
        $appTrackingMessage = 'Test message';
        $productionAppVersion = '1.0.0';

        $mobileConfig = $this->generateMobileConfigMock();

        $this->mobileConfigManagementServiceMock->storeMobileConfig(
            $mobileSplashScreenType,
            $mobileWelcomeScreenLogoType,
            $mobilePreviewStatus,
            $appTrackingMessageEnabled,
            $appTrackingMessage,
            $productionAppVersion
        )
            ->shouldBeCalledOnce()
            ->willReturn($mobileConfig);

        $this->mobileConfig(
            $loggedInUser,
            $mobileSplashScreenType,
            $mobileWelcomeScreenLogoType,
            $mobilePreviewStatus,
            $appTrackingMessageEnabled,
            $appTrackingMessage,
            $productionAppVersion
        )->shouldReturn($mobileConfig);
    }

    public function it_should_throw_exception_when_storing_mobile_config_fails(User $loggedInUser)
    {
        $mobileSplashScreenType = MobileSplashScreenTypeEnum::CONTAIN;
        $mobileWelcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::SQUARE;
        $mobilePreviewStatus = MobilePreviewStatusEnum::READY;
        $appTrackingMessageEnabled = true;
        $appTrackingMessage = 'Test message';
        $productionAppVersion = '1.0.0';

        $this->mobileConfigManagementServiceMock->storeMobileConfig(
            $mobileSplashScreenType,
            $mobileWelcomeScreenLogoType,
            $mobilePreviewStatus,
            $appTrackingMessageEnabled,
            $appTrackingMessage,
            $productionAppVersion
        )
            ->shouldBeCalled()
            ->willThrow(new \Exception('Test exception'));

        $this->shouldThrow(GraphQLException::class)->duringMobileConfig(
            $loggedInUser,
            $mobileSplashScreenType,
            $mobileWelcomeScreenLogoType,
            $mobilePreviewStatus,
            $appTrackingMessageEnabled,
            $appTrackingMessage,
            $productionAppVersion
        );
    }
}
