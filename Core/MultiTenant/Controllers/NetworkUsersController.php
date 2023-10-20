<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Services\NetworkUsersService;
use Minds\Core\MultiTenant\Types\NetworkUser;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\User;
use Minds\Exceptions\StopEventException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class NetworkUsersController
{
    public function __construct(
        private readonly NetworkUsersService $service
    ) {
    }

    /**
     * @param NetworkUser $networkUser
     * @param User|null $loggedInUser
     * @return NetworkUser
     * @throws UnverifiedEmailException
     * @throws StopEventException
     */
    #[Mutation]
    #[Logged]
    public function createNetworkRootUser(
        NetworkUser $networkUser,
        #[InjectUser] ?User $loggedInUser = null,
    ): NetworkUser {
        return $this->service->createNetworkRootUser($networkUser, $loggedInUser);
    }
}
