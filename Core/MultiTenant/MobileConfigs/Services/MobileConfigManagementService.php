<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Services;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\MobileConfigs\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;

class MobileConfigManagementService
{
    public function __construct(
        private readonly MobileConfigRepository $mobileConfigRepository,
        private readonly MobilePreviewHandler   $mobilePreviewHandler,
    ) {
    }

    /**
     * @param int $tenantId
     * @param string $status
     * @return void
     */
    public function processMobilePreviewWebhook(
        int    $tenantId,
        string $appVersion,
        string $status = 'success'
    ): void {
        $this->mobileConfigRepository->storeMobileConfig(
            tenantId: $tenantId,
            appVersion: $appVersion,
            previewStatus: $status === 'success' ? MobilePreviewStatusEnum::READY : MobilePreviewStatusEnum::ERROR,
        );
    }

    /**
     * @param MobileSplashScreenTypeEnum|null $mobileSplashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $mobileWelcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $mobilePreviewStatus
     * @param bool|null $appTrackingMessageEnabled
     * @param string|null $appTrackingMessage
     * @param string|null $productionAppVersion
     * @return MobileConfig
     * @throws GuzzleException
     */
    public function storeMobileConfig(
        ?MobileSplashScreenTypeEnum      $mobileSplashScreenType,
        ?MobileWelcomeScreenLogoTypeEnum $mobileWelcomeScreenLogoType,
        ?MobilePreviewStatusEnum         $mobilePreviewStatus,
        ?bool                            $appTrackingMessageEnabled,
        ?string                          $appTrackingMessage,
        ?string                          $productionAppVersion
    ): MobileConfig {
        try {
            $mobileConfig = $this->mobileConfigRepository->getMobileConfig();
        } catch (NoMobileConfigFoundException $e) {
            $mobileConfig = null;
        }

        if ($mobilePreviewStatus !== MobilePreviewStatusEnum::PENDING) {
            $mobilePreviewStatus = null;
        }

        $this->mobileConfigRepository->storeMobileConfig(
            splashScreenType: $mobileSplashScreenType,
            welcomeScreenLogoType: $mobileWelcomeScreenLogoType,
            previewStatus: $mobilePreviewStatus,
            appTrackingMessageEnabled: $appTrackingMessageEnabled !== null ?
                $appTrackingMessageEnabled :
                $mobileConfig?->appTrackingMessageEnabled,
            appTrackingMessage: $appTrackingMessage !== null ?
                $appTrackingMessage :
                $mobileConfig?->appTrackingMessage,
            productionAppVersion: $productionAppVersion !== null ?
                $productionAppVersion :
                $mobileConfig?->productionAppVersion
        );

        if ($mobilePreviewStatus === MobilePreviewStatusEnum::PENDING) {
            $this->mobilePreviewHandler->triggerPipeline();
        }

        return new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: $mobileSplashScreenType ?? ($mobileConfig?->splashScreenType ?? MobileSplashScreenTypeEnum::CONTAIN),
            welcomeScreenLogoType: $mobileWelcomeScreenLogoType ?? ($mobileConfig?->welcomeScreenLogoType ?? MobileWelcomeScreenLogoTypeEnum::SQUARE),
            previewStatus: $mobilePreviewStatus ?? ($mobileConfig?->previewStatus ?? MobilePreviewStatusEnum::NO_PREVIEW),
            previewLastUpdatedTimestamp: $mobilePreviewStatus ? time() : $mobileConfig?->previewLastUpdatedTimestamp,
            appTrackingMessageEnabled: $appTrackingMessageEnabled ?? ($mobileConfig?->appTrackingMessageEnabled ?? false),
            appTrackingMessage: $appTrackingMessage ?? ($mobileConfig?->appTrackingMessage ?? null),
            productionAppVersion: $productionAppVersion ?? ($mobileConfig?->productionAppVersion ?? null)
        );
    }
}
