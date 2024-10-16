<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MetadataExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\WebsiteIconExtractor;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateLogosDelegate;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserAvatarDelegate;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\HorizontalLogoExtractor;
use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\MobileSplashLogoExtractor;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use Minds\Helpers\Image as ImageHelpers;

/**
 * Handles the generation of logos.
 */
class LogoGenerationHandler
{
    public function __construct(
        private MetadataExtractor $metadataExtractor,
        private WebsiteIconExtractor $websiteIconExtractor,
        private HorizontalLogoExtractor $horizontalLogoExtractor,
        private MobileSplashLogoExtractor $mobileSplashLogoExtractor,
        private UpdateLogosDelegate $updateLogosDelegate,
        private UpdateUserAvatarDelegate $updateUserAvatarDelegate,
        private BootstrapProgressRepository $progressRepository,
        private ImageHelpers $imageHelpers,
        private Logger $logger,
    ) {
    }

    /**
     * Handles the generation of logos.
     * @param string $siteUrl - The URL of the website to generate logos from.
     * @param User $rootUser - The root user.
     * @return void
     */
    public function handle(string $siteUrl, User $rootUser): void
    {
        try {
            $this->logger->info("Extracting logos...");

            $squareLogoBlob = $this->getSquareLogoBlob($siteUrl);
            $faviconBlob = $this->getFaviconBlob($siteUrl);

            if (!$squareLogoBlob && !$faviconBlob) {
                throw new ServerErrorException("No logos found");
            }

            $horizontalLogoBlob = null;
            $splashBlob = null;

            if ($squareLogoBlob) {
                $horizontalLogoBlob = $this->getHorizontalLogoBlob($squareLogoBlob);
                $splashBlob = $this->getSplashBlob($squareLogoBlob);
            }

            if (!$this->updateLogosDelegate->onUpdate(
                squareLogoBlob: $squareLogoBlob,
                faviconBlob: $faviconBlob,
                horizontalLogoBlob: $horizontalLogoBlob,
                splashBlob: $splashBlob
            )) {
                throw new ServerErrorException("Failed to upload all logos");
            }

            if ($squareLogoBlob) {
                $this->updateUserAvatar($squareLogoBlob, $rootUser);
            }

            $this->progressRepository->updateProgress(BootstrapStepEnum::LOGO_STEP, true);
            $this->logger->info("Updated bootstrap progress for logos step to success");
        } catch (\Exception $e) {
            $this->logger->error("Error extracting logos: " . $e->getMessage());
            $this->progressRepository->updateProgress(BootstrapStepEnum::LOGO_STEP, false);
            $this->logger->info("Updated bootstrap progress for logos step to failed");
        }
    }

    /**
     * Retrieves the square logo blob.
     * @param string $siteUrl - The URL of the website to retrieve the square logo from.
     * @return string|null - The square logo blob or null if not found.
     */
    private function getSquareLogoBlob(string $siteUrl): ?string
    {
        try {
            $squareLogoBlob = $this->websiteIconExtractor->extract($siteUrl, 256);

            if (!$squareLogoBlob || !$this->imageHelpers->isValidImage($squareLogoBlob)) {
                throw new ServerErrorException("Valid square logo not extracted");
            }

            $this->logger->info("Square logo retrieved");
            return $squareLogoBlob;
        } catch (\Exception $e) {
            $this->logger->error("Error retrieving square logo: " . $e->getMessage());
            return null;
        }

    }

    /**
     * Retrieves the favicon blob.
     * @param string $siteUrl - The URL of the website to retrieve the favicon from.
     * @return string|null - The favicon blob or null if not found.
     */
    private function getFaviconBlob(string $siteUrl): ?string
    {
        try {
            $faviconBlob = $this->websiteIconExtractor->extract($siteUrl, 32);

            if (!$faviconBlob || !$this->imageHelpers->isValidImage($faviconBlob)) {
                throw new ServerErrorException("Valid favicon not extracted");
            }

            $this->logger->info("Favicon retrieved");
            return $faviconBlob;
        } catch (\Exception $e) {
            $this->logger->error("Error retrieving favicon: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves the horizontal logo blob.
     * @param string $squareLogoBlob - The square logo blob to retrieve the horizontal logo from.
     * @return string|null - The horizontal logo blob or null if not found.
     */
    private function getHorizontalLogoBlob(string $squareLogoBlob): ?string
    {
        try {
            $horizontalLogoBlob = $this->horizontalLogoExtractor->extract($squareLogoBlob);
            
            if (!$horizontalLogoBlob || !$this->imageHelpers->isValidImage($horizontalLogoBlob)) {
                throw new ServerErrorException("Valid horizontal logo not extracted");
            }

            $this->logger->info("Horizontal logo retrieved");
            return $horizontalLogoBlob;
        } catch (\Exception $e) {
            $this->logger->error("Error retrieving horizontal logo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retrieves the splash blob.
     * @param string $squareLogoBlob - The square logo blob to retrieve the splash logo from.
     * @return string|null - The splash logo blob or null if not found.
     */
    private function getSplashBlob(string $squareLogoBlob): ?string
    {
        try {
            $splashBlob = $this->mobileSplashLogoExtractor->extract($squareLogoBlob);

            if (!$splashBlob || !$this->imageHelpers->isValidImage($splashBlob)) {
                throw new ServerErrorException("Valid splash logo not extracted");
            }

            $this->logger->info("Splash logo retrieved");
            return $splashBlob;
        } catch (\Exception $e) {
            $this->logger->error("Error retrieving splash logo: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Updates the user avatar.
     * @param string $squareLogoBlob - The square logo blob.
     * @param User $rootUser - The root user.
     * @throws ServerErrorException - If the user avatar upload fails to update.
     * @return void
     */
    private function updateUserAvatar(string $squareLogoBlob, User $rootUser): void
    {
        if (!$this->updateUserAvatarDelegate->onUpdate(
            user: $rootUser,
            imageBlob: $squareLogoBlob
        )) {
            throw new ServerErrorException("Failed to update user avatar");
        }
        $this->logger->info("Updated user avatar");
    }
}
