<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\Enums\MobileWelcomeScreenLogoTypeEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class MobileConfig
{
    public function __construct(
        #[Field] public MobileSplashScreenTypeEnum      $splashScreenType,
        #[Field] public MobileWelcomeScreenLogoTypeEnum $welcomeScreenLogoType,
        #[Field] public MobilePreviewStatusEnum         $previewStatus,
        #[Field] public int                             $updateTimestamp,
        #[Field] public ?string                         $previewQRCode = null,
        public ?int                                     $previewLastUpdatedTimestamp = null,
    ) {
    }
}