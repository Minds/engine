<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\Deployments\Builds\MobilePreviewHandler;
use Minds\Core\MultiTenant\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\Repositories\MobileConfigRepository;
use Minds\Core\MultiTenant\Types\MobileConfig;

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
        string $status = 'success'
    ): void {
        $this->mobileConfigRepository->storeMobileConfig(
            tenantId: $tenantId,
            previewStatus: $status === 'success' ? MobilePreviewStatusEnum::READY : MobilePreviewStatusEnum::ERROR,
        );
    }

    /**
     * @param MobileSplashScreenTypeEnum|null $mobileSplashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $mobileWelcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $mobilePreviewStatus
     * @return MobileConfig
     * @throws GuzzleException
     */
    public function storeMobileConfig(
        ?MobileSplashScreenTypeEnum      $mobileSplashScreenType,
        ?MobileWelcomeScreenLogoTypeEnum $mobileWelcomeScreenLogoType,
        ?MobilePreviewStatusEnum         $mobilePreviewStatus
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
        );
    }
}
