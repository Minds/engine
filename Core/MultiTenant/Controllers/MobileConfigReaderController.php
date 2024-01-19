<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Common\Jwt;
use Minds\Core\Config\Config;
use Minds\Core\MultiTenant\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\Traits\MobilePreviewJwtTokenTrait;
use Minds\Core\MultiTenant\Types\AppReadyMobileConfig;
use Minds\Core\MultiTenant\Types\MobileConfig;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use Zend\Diactoros\ServerRequestFactory;

class MobileConfigReaderController
{
    use MobilePreviewJwtTokenTrait;

    public function __construct(
        private readonly MobileConfigReaderService $mobileConfigReaderService,
        private readonly Jwt                       $jwt,
        private readonly Config                    $config
    ) {
    }

    #[Query]
    /**
     * @param int $tenantId
     * @return AppReadyMobileConfig
     * @throws GraphQLException
     * @throws NoMobileConfigFoundException
     */
    public function appReadyMobileConfig(
        int $tenantId
    ): AppReadyMobileConfig {
        $jwtToken = (ServerRequestFactory::fromGlobals())->getHeader('token');
        if (!$this->checkToken($jwtToken[0])) {
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
