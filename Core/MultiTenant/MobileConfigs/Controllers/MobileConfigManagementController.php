<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobilePreviewStatusEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileSplashScreenTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Enums\MobileWelcomeScreenLogoTypeEnum;
use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigManagementService;
use Minds\Core\MultiTenant\MobileConfigs\Services\ProductionAppVersionService;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use Zend\Diactoros\ServerRequestFactory;

class MobileConfigManagementController
{
    public function __construct(
        private readonly MobileConfigManagementService $mobileConfigManagementService,
        private readonly ProductionAppVersionService $productionAppVersionService,
        private readonly GitlabPipelineJwtTokenValidator $gitlabPipelineJwtTokenValidator,
    ) {
    }

    /**
     * @param MobileSplashScreenTypeEnum|null $mobileSplashScreenType
     * @param MobileWelcomeScreenLogoTypeEnum|null $mobileWelcomeScreenLogoType
     * @param MobilePreviewStatusEnum|null $mobilePreviewStatus
     * @param bool $appTrackingMessageEnabled
     * @param string $appTrackingMessage
     * @param string|null $productionAppVersion
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
        ?bool                            $appTrackingMessageEnabled,
        ?string                          $appTrackingMessage,
        ?string                          $productionAppVersion,
    ): MobileConfig {
        try {
            return $this->mobileConfigManagementService->storeMobileConfig(
                mobileSplashScreenType: $mobileSplashScreenType,
                mobileWelcomeScreenLogoType: $mobileWelcomeScreenLogoType,
                mobilePreviewStatus: $mobilePreviewStatus,
                appTrackingMessageEnabled: $appTrackingMessageEnabled,
                appTrackingMessage: $appTrackingMessage,
                productionAppVersion: $productionAppVersion
            );
        } catch (Exception $e) {
            throw new GraphQLException($e->getMessage());
        }
    }

    /**
     * Set the mobile production app version for a tenant.
     * @param int $tenantId - The tenant ID.
     * @param string $productionAppVersion - The production app version.
     * @return bool
     */
    #[Mutation]
    public function mobileProductionAppVersion(
        int $tenantId,
        string $productionAppVersion
    ): bool {
        try {
            $jwtToken = (ServerRequestFactory::fromGlobals())->getHeader('token');
            if (!$this->gitlabPipelineJwtTokenValidator->checkToken($jwtToken[0], $tenantId)) {
                throw new GraphQLException('Invalid token', 403);
            }
            return $this->productionAppVersionService->setProductionMobileAppVersion($tenantId, $productionAppVersion);
        } catch (Exception $e) {
            throw new GraphQLException($e->getMessage());
        }
    }

    /**
     * Clear the mobile production app version for all tenants.
     * @return bool
     */
    #[Mutation]
    public function clearAllMobileAppVersions(): bool
    {
        $jwtToken = (ServerRequestFactory::fromGlobals())->getHeader('token');
        // Expects the token to be for the tenant network `-1`.
        if (!$this->gitlabPipelineJwtTokenValidator->checkToken($jwtToken[0], -1)) {
            throw new GraphQLException('Invalid token', 403);
        }
        return $this->productionAppVersionService->clearForAllTenants();
    }
}
