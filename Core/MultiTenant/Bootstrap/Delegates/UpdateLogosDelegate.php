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
use Minds\Exceptions\ServerErrorException;

/**
 * Delegate for updating the logos.
 */
class UpdateLogosDelegate
{
    /** Whether any uploads failed. */
    private bool $failedUpload = false;

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
     * @return bool - Whether the upload was successful.
     */
    public function onUpdate(
        string $squareLogoBlob = null,
        string $faviconBlob = null,
        string $horizontalLogoBlob = null,
        string $splashBlob = null
    ): bool {
        $this->failedUpload = false;

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

        if ($this->failedUpload) {
            $this->logger->error("Failed to upload all logos, some may have been saved.");
        } else {
            $this->logger->info("Done uploading logos");
        }

        return !$this->failedUpload;
    }

    /**
     * Upload the square logo.
     * @param string $bigIconBlob - The blob of the square logo.
     * @return void
     */
    private function uploadSquareLogo(string $bigIconBlob): void
    {
        try {
            if ($this->configImageManager->uploadBlob($bigIconBlob, MultiTenantConfigImageType::SQUARE_LOGO)) {
                $this->logger->info("Uploaded web square logo");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
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
            if ($this->configImageManager->uploadBlob($logoBlob, MultiTenantConfigImageType::HORIZONTAL_LOGO)) {
                $this->logger->info("Uploaded web horizontal logo");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
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
            if ($this->configImageManager->uploadBlob($faviconBlob, MultiTenantConfigImageType::FAVICON)) {
                $this->logger->info("Uploaded web favicon");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
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
            if ($this->mobileConfigAssetsService->uploadBlob($logoBlob, MobileConfigImageTypeEnum::HORIZONTAL_LOGO)) {
                $this->logger->info("Uploaded mobile horizontal logo");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
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
            if ($this->mobileConfigAssetsService->uploadBlob($bigIconBlob, MobileConfigImageTypeEnum::ICON)) {
                $this->logger->info("Uploaded mobile icon");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
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
            if ($this->mobileConfigAssetsService->uploadBlob($bigIconBlob, MobileConfigImageTypeEnum::SQUARE_LOGO)) {
                $this->logger->info("Uploaded mobile square logo");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
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
            if ($this->mobileConfigAssetsService->uploadBlob($splashBlob, MobileConfigImageTypeEnum::SPLASH)) {
                $this->logger->info("Uploaded mobile splash");
            } else {
                throw new ServerErrorException("Upload failed");
            }
        } catch (\Exception $e) {
            $this->failedUpload = true;
            $this->logger->error("Failed to upload mobile splash: " . $e->getMessage());
        }
    }
}
