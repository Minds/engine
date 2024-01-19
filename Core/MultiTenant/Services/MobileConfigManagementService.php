<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

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
    ) {
    }

    /**
     * @param int $tenantId
     * @param string $status
     * @return bool
     */
    public function processMobilePreviewWebhook(
        int    $tenantId,
        string $status = 'success'
    ): void {
        $this->mobileConfigRepository->storeMobileConfig(
            previewStatus: $status === 'success' ? MobilePreviewStatusEnum::READY : MobilePreviewStatusEnum::ERROR,
        );
    }

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

        if ($mobilePreviewStatus?->value !== MobilePreviewStatusEnum::PENDING) {
            $mobilePreviewStatus = null;
        }

        $this->mobileConfigRepository->storeMobileConfig(
            splashScreenType: $mobileSplashScreenType,
            welcomeScreenLogoType: $mobileWelcomeScreenLogoType,
            previewStatus: $mobilePreviewStatus,
        );

        return new MobileConfig(
            updateTimestamp: time(),
            splashScreenType: $mobileSplashScreenType ?? $mobileConfig?->splashScreenType,
            welcomeScreenLogoType: $mobileWelcomeScreenLogoType ?? $mobileConfig?->welcomeScreenLogoType,
            previewStatus: $mobilePreviewStatus ?? ($mobileConfig?->previewStatus ?? MobilePreviewStatusEnum::NO_PREVIEW),
            previewQRCode: $mobileConfig?->previewQRCode,
            previewLastUpdatedTimestamp: $mobilePreviewStatus ? time() : $mobileConfig?->previewLastUpdatedTimestamp,
        );
    }
}
