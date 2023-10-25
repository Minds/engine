<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Exception;
use Minds\Core\Config\Config;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\MultiTenant\Enums\NetworkUserRoleEnum;
use Minds\Core\MultiTenant\Exceptions\NoTenantFoundException;
use Minds\Core\MultiTenant\Repositories\TenantUsersRepository;
use Minds\Core\MultiTenant\Types\NetworkUser;
use Minds\Core\Router\Exceptions\UnverifiedEmailException;
use Minds\Entities\User;
use Minds\Exceptions\StopEventException;
use RegistrationException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class NetworkUsersService
{
    public function __construct(
        private readonly TenantUsersRepository $tenantUsersRepository,
        private readonly SaveAction $saveAction,
        private readonly Config $mindsConfig,
        private readonly MultiTenantBootService $multiTenantBootService,
    ) {
    }

    /**
     * Creates root account for a network user.
     * @param NetworkUser $networkUser - network user object to build root user.
     * @param User $sourceUser - source user to generate from.
     * @return NetworkUser
     * @throws UnverifiedEmailException
     * @throws StopEventException
     * @throws Exception
     */
    public function createNetworkRootUser(NetworkUser $networkUser, User $sourceUser): NetworkUser
    {
        if ($this->tenantUsersRepository->getTenantRootAccount($networkUser->tenantId)) {
            throw new GraphQLException('Root account already exists.');
        }

        // create the user.
        $newUser = $this->buildUser($networkUser, $sourceUser);

        // write to entities table & write to users table.
        $this->saveAction->setEntity($newUser)->save();

        // update tenant table with generated owner_guid.
        $this->tenantUsersRepository->setTenantRootAccount($networkUser->tenantId, (int) $newUser->guid);

        $networkUser->role = NetworkUserRoleEnum::OWNER;
        return $networkUser;
    }

    /**
     * Builds a new user from given network user and logged-in user.
     * @param NetworkUser $networkUser - network user to generate from.
     * @param User $sourceUser - source user to generate from.
     * @return User - build user.
     * @throws NoTenantFoundException
     * @throws RegistrationException
     */
    private function buildUser(
        NetworkUser $networkUser,
        User $sourceUser
    ): User {
        // DO NOT REMOVE THIS CONFIG SETTING
        $this->multiTenantBootService->bootFromTenantId($networkUser->tenantId);

        // Create the user
        $user = register_user(
            username: $networkUser->username,
            password: $networkUser->plainPassword,
            name: $sourceUser->getName(),
            email: $sourceUser->getEmail(),
            validatePassword: false,
            isActivityPub: false
        );

        $user->set('tenant_id', $networkUser->tenantId);
        $user->set('admin', true);

        $this->multiTenantBootService->resetRootConfigs();

        return $user;
    }
}
