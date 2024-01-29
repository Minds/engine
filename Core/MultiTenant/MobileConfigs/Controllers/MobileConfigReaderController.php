<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\MobileConfigs\Controllers;

use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\MobileConfigs\Helpers\GitlabPipelineJwtTokenValidator;
use Minds\Core\MultiTenant\MobileConfigs\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\MobileConfigs\Types\AppReadyMobileConfig;
use Minds\Core\MultiTenant\MobileConfigs\Types\MobileConfig;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use Zend\Diactoros\ServerRequestFactory;

class MobileConfigReaderController
{
    public function __construct(
        private readonly MobileConfigReaderService       $mobileConfigReaderService,
        private readonly GitlabPipelineJwtTokenValidator $gitlabPipelineJwtTokenValidator,
    ) {
    }

    #[Query]
    /**
     * @param int $tenantId
     * @return AppReadyMobileConfig
     * @throws GraphQLException
     * @throws NoTenantFoundException
     */
    public function appReadyMobileConfig(
        int $tenantId
    ): AppReadyMobileConfig {
        $jwtToken = (ServerRequestFactory::fromGlobals())->getHeader('token');
        if (!$this->gitlabPipelineJwtTokenValidator->checkToken($jwtToken[0])) {
            throw new GraphQLException('Invalid token', 403);
        }
        return $this->mobileConfigReaderService->getAppReadyMobileConfig($tenantId);
    }

    /**
     * @return MobileConfig
     */
    #[Query]
    #[Logged]
    #[Security('is_granted("ROLE_ADMIN", loggedInUser)')]
    public function mobileConfig(
        #[InjectUser] User $loggedInUser
    ): MobileConfig {
        return $this->mobileConfigReaderService->getMobileConfig();
    }
}
