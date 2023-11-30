<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Services\TenantUsersService;
use Minds\Core\MultiTenant\Types\TenantUser;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\User;
use Minds\Exceptions\StopEventException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class TenantUsersController
{
    public function __construct(
        private readonly TenantUsersService $service
    ) {
    }

    /**
     * @param TenantUser $networkUser
     * @param User|null $loggedInUser
     * @return TenantUser
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    #[Mutation]
    #[Logged]
    public function createNetworkRootUser(
        TenantUser          $networkUser,
        #[InjectUser] ?User $loggedInUser = null,
    ): TenantUser {
        return $this->service->createNetworkRootUser($networkUser, $loggedInUser);
    }
}
