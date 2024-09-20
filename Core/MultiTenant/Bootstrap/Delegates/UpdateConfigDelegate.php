<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\MultiTenant\Configs\Manager as MultiTenantConfigManager;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;

/**
 * Delegate for updating the config.
 */
class UpdateConfigDelegate
{
    public function __construct(
        private MultiTenantConfigManager $multiTenantConfigManager
    ) {
    }

    /**
     * Update the config.
     * @param string $siteName - The name of the site.
     * @param MultiTenantColorScheme $colorScheme - The color scheme.
     * @param string $primaryColor - The primary color.
     * @param string $description - The description.
     * @return void
     */
    public function onUpdate(
        string $siteName = null,
        MultiTenantColorScheme $colorScheme = null,
        string $primaryColor = null,
        string $description = null
    ): void {
        $this->multiTenantConfigManager->upsertConfigs(
            siteName: $siteName,
            colorScheme: $colorScheme,
            primaryColor: $primaryColor,
            customHomePageDescription: $description
        );
    }
}
