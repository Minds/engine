<?php
namespace Minds\Core\Security\Rbac\Controllers;

use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PermissionsController
{
    public function __construct(
        private readonly RolesService $rolesService
    ) {
        
    }

    /**
     * Returns the permissions that the current session holds
     * @return string[]
     */
    #[Query]
    #[Logged]
    public function getAssignedPermissions(
        #[InjectUser] ?User $loggedInUser = null,
    ): array {
        return $this->rolesService->getUserPermissions($loggedInUser);
    }

    /**
     * Returns the roles the session holds
     * @return Role[]
     */
    #[Query]
    #[Logged]
    public function getAssignedRoles(
        #[InjectUser] ?User $loggedInUser = null,
    ): array {
        return $this->rolesService->getRoles($loggedInUser);
    }

    /**
     * Returns all roles that exist on the site and their permission assignments
     * @return Role[]
     */
    #[Query]
    public function getAllRoles(): array
    {
        return $this->rolesService->getAllRoles();
    }

    /**
     * Returns all permissions that exist on the site
     * @return PermissionsEnum[]
     */
    #[Query]
    public function getAllPermissions(): array
    {
        return $this->rolesService->getAllPermissions();
    }
}
