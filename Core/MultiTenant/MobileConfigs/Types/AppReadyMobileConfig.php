<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Types;

use Minds\Core\GraphQL\Types\KeyValueType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * This model is used to return the mobile configuration to the GitLab CI pipeline to generate the tenant's mobile app.
 */
#[Type]
class AppReadyMobileConfig
{
    public function __construct(
        #[Field(name: "APP_NAME")] public readonly string           $appName,
        #[Field(name: "TENANT_ID")] public readonly int             $tenantId,
        #[Field(name: "APP_HOST")] public readonly string           $appHost, // this is the tenant's domain
        #[Field(name: "APP_SPLASH_RESIZE")] public readonly string  $appSplashResize,
        #[Field(name: "ACCENT_COLOR_LIGHT")] public readonly string $accentColorLight,
        #[Field(name: "ACCENT_COLOR_DARK")] public readonly string  $accentColorDark,
        #[Field(name: "WELCOME_LOGO")] public readonly string       $welcomeLogoType,
        #[Field(name: "THEME")] public readonly string              $theme,
        #[Field(name: "API_URL")] public readonly string            $apiUrl, // this is the tenant's domain
        private readonly array                                      $assets,
    ) {
    }

    /**
     * @return KeyValueType[]
     */
    #[Field]
    public function getAssets(): array
    {
        return $this->assets;
    }
}
