<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Services\Handlers;

use Minds\Core\MultiTenant\Bootstrap\Services\Extractors\ThemeExtractor;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateConfigDelegate;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Enums\BootstrapStepEnum;
use Minds\Core\MultiTenant\Bootstrap\Repositories\BootstrapProgressRepository;

/**
 * Handles the generation of a tenant's config.
 */
class ConfigGenerationHandler
{
    public function __construct(
        private ThemeExtractor $themeExtractor,
        private UpdateConfigDelegate $updateConfigDelegate,
        private BootstrapProgressRepository $progressRepository,
        private Logger $logger
    ) {
    }

    /**
     * Handles the generation of a tenant's config.
     * @param string|null $screenshotBlob - The screenshot of the website.
     * @param string|null $description - The description of the website.
     * @param string|null $siteName - The name of the website.
     */
    public function handle(?string $screenshotBlob, ?string $description, ?string $siteName)
    {
        try {
            $this->logger->info("Extracting tenant config...");

            $theme = null;
            $colorScheme = null;
            $primaryColor = null;

            if ($screenshotBlob) {
                $theme = $this->themeExtractor->extract($screenshotBlob);
                $this->logger->info("Theme data: " . json_encode($theme));

                if ($theme) {
                    $colorScheme = $theme['theme'] === 'light' ? MultiTenantColorScheme::LIGHT : MultiTenantColorScheme::DARK;
                    $primaryColor = $theme['color'] ?? null;
                }
            }

            $this->updateConfigDelegate->onUpdate(
                siteName: $siteName,
                colorScheme: $colorScheme,
                primaryColor: $primaryColor,
                description: $description
            );

            $this->logger->info("Tenant config updated");
            $this->progressRepository->updateProgress(BootstrapStepEnum::TENANT_CONFIG_STEP, true);
            $this->logger->info("Updated bootstrap progress for tenant config step to success");
        } catch (\Exception $e) {
            $this->logger->error("Error extracting tenant config: " . $e->getMessage());
            $this->progressRepository->updateProgress(BootstrapStepEnum::TENANT_CONFIG_STEP, false);
            $this->logger->info("Updated bootstrap progress for tenant config step to failed");
        }
    }
}
