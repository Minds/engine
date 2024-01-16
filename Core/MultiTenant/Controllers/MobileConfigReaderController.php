<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Exceptions\NoMobileConfigFoundException;
use Minds\Core\MultiTenant\Services\MobileConfigReaderService;
use Minds\Core\MultiTenant\Types\AppReadyMobileConfig;
use Minds\Core\MultiTenant\Types\MobileConfig;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class MobileConfigReaderController
{
    public function __construct(
        private readonly MobileConfigReaderService $mobileConfigReaderService,
    ) {
    }

    #[Query]
    /**
     * @param int $tenantId
     * @return AppReadyMobileConfig
     * @throws NoMobileConfigFoundException
     */
    public function appReadyMobileConfig(
        int $tenantId
    ): AppReadyMobileConfig {
        return $this->mobileConfigReaderService->getAppReadyMobileConfig($tenantId);
    }

    /**
     * @return MobileConfig
     * @throws NoMobileConfigFoundException
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
