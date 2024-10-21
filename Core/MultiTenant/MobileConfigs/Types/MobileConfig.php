<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Types;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class MobileConfig
{
    /** Default app tracking message. */
    const DEFAULT_APP_TRACKING_MESSAGE = 'Allow this app to collect app-related data that can be used for tracking you or your device.';

    public function __construct(
        #[Field] public int                             $updateTimestamp,
        #[Field] public MobileSplashScreenTypeEnum      $splashScreenType = MobileSplashScreenTypeEnum::CONTAIN,
        #[Field] public MobileWelcomeScreenLogoTypeEnum $welcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::SQUARE,
        #[Field] public MobilePreviewStatusEnum         $previewStatus = MobilePreviewStatusEnum::NO_PREVIEW,
        public ?int                                     $previewLastUpdatedTimestamp = null,
        #[Field] public ?string                         $productionAppVersion = null, // app version of tenant app in production.
        #[Field] public ?string                         $appVersion = null, // app version for previewer.
        public ?string                                  $easProjectId = null,
        public ?string                                  $appSlug = null,
        public ?string                                  $appScheme = null,
        public ?string                                  $appIosBundle = null,
        public ?string                                  $appAndroidPackage = null,
        public ?string                                  $androidKeystoreFingerprint = null,
        public ?string                                  $appleDevelopmentTeamId = null,
        #[Field] public ?bool                           $appTrackingMessageEnabled = null,
        #[Field] public ?string                         $appTrackingMessage = self::DEFAULT_APP_TRACKING_MESSAGE
    ) {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("mobile_config_" . Di::_()->get(Config::class)->get("tenant_id"));
    }

    #[Field]
    public function getPreviewQRCode(): string
    {
        return "mindspreview://preview/" . Di::_()->get(Config::class)->get("tenant_id") . "?version=$this->appVersion";
    }
}
