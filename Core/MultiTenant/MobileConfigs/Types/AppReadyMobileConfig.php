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
        #[Field(name: "APP_NAME")] public readonly string                $appName,
        #[Field(name: "TENANT_ID")] public readonly int                  $tenantId,
        #[Field(name: "APP_HOST")] public readonly string                $appHost, // this is the tenant's domain
        #[Field(name: "APP_SPLASH_RESIZE")] public readonly string       $appSplashResize,
        #[Field(name: "ACCENT_COLOR_LIGHT")] public readonly string      $accentColorLight,
        #[Field(name: "ACCENT_COLOR_DARK")] public readonly string       $accentColorDark,
        #[Field(name: "WELCOME_LOGO")] public readonly string            $welcomeLogoType,
        #[Field(name: "THEME")] public readonly string                   $theme,
        #[Field(name: "API_URL")] public readonly string                 $apiUrl, // this is the tenant's domain
        private readonly array                                           $assets,
        #[Field(name: "PRODUCTION_APP_VERSION")] public readonly ?string $productionAppVersion = null, // This is the production app version
        #[Field(name: "EAS_PROJECT_ID")] public readonly ?string         $easProjectId = null,
        #[Field(name: "APP_SLUG")] public readonly ?string               $appSlug = null, // The app slug in expo.dev
        #[Field(name: "APP_SCHEME")] public readonly ?string             $appScheme = null,
        #[Field(name: 'APP_IOS_BUNDLE')] public readonly ?string         $appIosBundle = null,
        #[Field(name: 'APP_ANDROID_PACKAGE')] public readonly ?string    $appAndroidPackage = null,
        #[Field(name: 'APP_TRACKING_MESSAGE_ENABLED')] public ?bool      $appTrackingMessageEnabled = null,
        #[Field(name: 'APP_TRACKING_MESSAGE')] public ?string            $appTrackingMessage = null,
        #[Field(name: 'IS_NON_PROFIT')] public ?bool                     $isNonProfit = null
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
