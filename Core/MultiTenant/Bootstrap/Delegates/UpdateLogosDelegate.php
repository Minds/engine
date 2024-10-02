<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantConfigImageType;
use Minds\Core\MultiTenant\Configs\Image\Manager as ConfigImageManager;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileConfigImageTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigAssetsService;

/**
 * Delegate for updating the logos.
 */
class UpdateLogosDelegate
{
    public function __construct(
        private ConfigImageManager $configImageManager,
        private MobileConfigAssetsService $mobileConfigAssetsService,
        private HorizontalLogoExtractor $horizontalLogoExtractor,
        private MobileSplashLogoExtractor $mobileSplashLogoExtractor,
        private Logger $logger
    ) {
    }

    /**
     * Update the logos.
     * @param string $squareLogoBlob - The blob of the square logo.
     * @param string $faviconBlob - The blob of the favicon.
     * @param string $horizontalLogoBlob - The blob of the horizontal logo.
     * @param string $splashBlob - The blob of the splash.
     * @return void
     */
    public function onUpdate(
        string $squareLogoBlob = null,
        string $faviconBlob = null,
        string $horizontalLogoBlob = null,
        string $splashBlob = null
    ) {
        if ($squareLogoBlob) {
            $this->uploadSquareLogo($squareLogoBlob);
            $this->uploadMobileIcon($squareLogoBlob);
            $this->uploadMobileSquareLogo($squareLogoBlob);
        }

        if ($horizontalLogoBlob) {
            $this->uploadHorizontalLogo($horizontalLogoBlob);
            $this->uploadMobileHorizontalLogo($horizontalLogoBlob);
        }

        if ($faviconBlob) {
            $this->uploadFavicon($faviconBlob);
        }

        if ($splashBlob) {
            $this->uploadMobileSplash($splashBlob);
        }
    }

    /**
     * Upload the square logo.
     * @param string $bigIconBlob - The blob of the square logo.
     * @return void
     */
    private function uploadSquareLogo(string $bigIconBlob): void
    {
        try {
            $this->configImageManager->uploadBlob($bigIconBlob, MultiTenantConfigImageType::SQUARE_LOGO);
            $this->logger->info("Uploaded web square logo");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload web square logo: " . $e->getMessage());
        }
    }

    /**
     * Upload the horizontal logo.
     * @param string $logoBlob - The blob of the horizontal logo.
     * @return void
     */
    private function uploadHorizontalLogo(string $logoBlob): void
    {
        try {
            $this->configImageManager->uploadBlob($logoBlob, MultiTenantConfigImageType::HORIZONTAL_LOGO);
            $this->logger->info("Uploaded web horizontal logo");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload web horizontal logo: " . $e->getMessage());
        }
    }

    /**
     * Upload the favicon.
     * @param string $faviconBlob - The blob of the favicon.
     * @return void
     */
    private function uploadFavicon(string $faviconBlob): void
    {
        try {
            $this->configImageManager->uploadBlob($faviconBlob, MultiTenantConfigImageType::FAVICON);
            $this->logger->info("Uploaded web favicon");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload web favicon: " . $e->getMessage());
        }
    }

    /**
     * Upload the mobile horizontal logo.
     * @param string $logoBlob - The blob of the horizontal logo.
     * @return void
     */
    private function uploadMobileHorizontalLogo(string $logoBlob): void
    {
        try {
            $this->mobileConfigAssetsService->uploadBlob($logoBlob, MobileConfigImageTypeEnum::HORIZONTAL_LOGO);
            $this->logger->info("Uploaded mobile horizontal logo");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload mobile horizontal logo: " . $e->getMessage());
        }
    }

    /**
     * Upload the mobile icon.
     * @param string $bigIconBlob - The blob of the square logo.
     * @return void
     */
    private function uploadMobileIcon(string $bigIconBlob): void
    {
        try {
            $this->mobileConfigAssetsService->uploadBlob($bigIconBlob, MobileConfigImageTypeEnum::ICON);
            $this->logger->info("Uploaded mobile icon");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload mobile icon: " . $e->getMessage());
        }
    }

    /**
     * Upload the mobile square logo.
     * @param string $bigIconBlob - The blob of the square logo.
     * @return void
     */
    private function uploadMobileSquareLogo(string $bigIconBlob): void
    {
        try {
            $this->mobileConfigAssetsService->uploadBlob($bigIconBlob, MobileConfigImageTypeEnum::SQUARE_LOGO);
            $this->logger->info("Uploaded mobile square logo");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload mobile square logo: " . $e->getMessage());
        }
    }

    /**
     * Upload the mobile splash.
     * @param string $splashBlob - The blob of the splash.
     * @return void
     */
    private function uploadMobileSplash(string $splashBlob): void
    {
        try {
            $this->mobileConfigAssetsService->uploadBlob($splashBlob, MobileConfigImageTypeEnum::SPLASH);
            $this->logger->info("Uploaded mobile splash");
        } catch (\Exception $e) {
            $this->logger->error("Failed to upload mobile splash: " . $e->getMessage());
        }
    }
}
