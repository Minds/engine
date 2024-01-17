<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\Types\MobileConfig;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

class MobileConfigManagementController
{
    public function __construct(
        private readonly MobileConfigManagementService $mobileConfigManagementService,
    ) {
    }

    #[Mutation]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function mobileConfig(
        #[InjectUser] User               $loggedInUser,
        ?MobileSplashScreenTypeEnum      $mobileSplashScreenType = null,
        ?MobileWelcomeScreenLogoTypeEnum $mobileWelcomeScreenLogoType = null,
        ?MobilePreviewStatusEnum         $mobilePreviewStatus = null,
    ): MobileConfig {
        return $this->mobileConfigManagementService->storeMobileConfig(
            mobileSplashScreenType: $mobileSplashScreenType,
            mobileWelcomeScreenLogoType: $mobileWelcomeScreenLogoType,
            mobilePreviewStatus: $mobilePreviewStatus,
        );
    }
}
