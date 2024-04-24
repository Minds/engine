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
    public function __construct(
        #[Field] public int                             $updateTimestamp,
        #[Field] public MobileSplashScreenTypeEnum      $splashScreenType = MobileSplashScreenTypeEnum::CONTAIN,
        #[Field] public MobileWelcomeScreenLogoTypeEnum $welcomeScreenLogoType = MobileWelcomeScreenLogoTypeEnum::SQUARE,
        #[Field] public MobilePreviewStatusEnum         $previewStatus = MobilePreviewStatusEnum::NO_PREVIEW,
        public ?int                                     $previewLastUpdatedTimestamp = null,
        public ?string                                  $appVersion = null,
        public ?string                                  $easProjectId = null,
        public ?string                                  $appSlug = null,
        public ?string                                  $appScheme = null,
        public ?string                                  $appIosBundle = null,
        public ?string                                  $appAndroidPackage = null,
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
