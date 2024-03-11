<?php
declare(strict_types=1);

namespace Minds\Core\PWA\Models;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;

/**
 * Tenant PWA Manifest model.
 */
class TenantManifest extends AbstractPWAManifest
{
    public function __construct(
        private ?Config $config = null
    ) {
        $this->config ??= Di::_()->get(Config::class);
        
        $siteName = $this->config->get('site_name');
        $themeColor = $this->getThemeColor();

        parent::__construct(
            name: $siteName,
            shortName: $siteName,
            description: 'A social app.',
            backgroundColor: $themeColor,
            themeColor: $themeColor,
            categories: ['social', 'news'],
            display: 'standalone',
            scope: './',
            startUrl: '/',
            icons: $this->getIcons(),
            preferRelatedApplications: false
        );
    }

    /**
     * Get icons array for tenant PWA manifest.
     * @return array - icons array.
     */
    private function getIcons(): array
    {
        return [
            [
                "src" => "/api/v3/multi-tenant/configs/image/square_logo",
                "type" => "image/png",
                "sizes" => "192x192"
            ]
        ];
    }

    /**
     * Get theme color for PWA manifest.
     * @return string - theme color.
     */
    private function getThemeColor(): string
    {
        return match($this->config->get('theme_override')['color_scheme'] ?? MultiTenantColorScheme::LIGHT) {
            MultiTenantColorScheme::DARK->value => '#010100',
            default => '#ffffff'
        };
    }
}
