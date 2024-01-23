<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\Types\MobileConfig;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class MobileConfigManagementController
{
    public function __construct(
        private readonly MobileConfigManagementService $mobileConfigManagementService,
    ) {
    }

    /**
     * @param MobileSplashScreenTypeEnum|null $mobileSplashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $mobileWelcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $mobilePreviewStatus
     * @return MobileConfig
     * @throws GraphQLException
     * @throws GuzzleException
     */
    #[Mutation]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function mobileConfig(
        #[InjectUser] User               $loggedInUser,
        ?MobileSplashScreenTypeEnum      $mobileSplashScreenType = null,
        ?MobileWelcomeScreenLogoTypeEnum $mobileWelcomeScreenLogoType = null,
        ?MobilePreviewStatusEnum         $mobilePreviewStatus = null,
    ): MobileConfig {
        try {
            return $this->mobileConfigManagementService->storeMobileConfig(
                mobileSplashScreenType: $mobileSplashScreenType,
                mobileWelcomeScreenLogoType: $mobileWelcomeScreenLogoType,
                mobilePreviewStatus: $mobilePreviewStatus,
            );
        } catch (Exception $e) {
            throw new GraphQLException($e->getMessage());
        }
    }
}
