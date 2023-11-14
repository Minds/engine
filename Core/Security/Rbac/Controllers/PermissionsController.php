<?php
namespace Minds\Core\Security\Rbac\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class PermissionsController
{
    public function __construct(
        private readonly RolesService $rolesService,
        private readonly EntitiesBuilder $entitiesBuilder,
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

    /**
     * Assigns a user to a role
     */
    #[Mutation]
    #[Logged]
    public function assignUserToRole(
        string $userGuid,
        int $roleId,
        #[InjectUser] ?User $loggedInUser = null,
    ): Role {
        // Only the Owner can assign permissions
        if (!$this->rolesService->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)) {
            throw new UserError("You don't have permission to assign roles");
        }

        $user = $this->entitiesBuilder->single($userGuid);
        if (!$user instanceof User) {
            throw new UserError("User not found");
        }
        
        $role = $this->rolesService->getRoleById($roleId);

        $this->rolesService->assignUserToRole($user, $role);

        return $role;
    }

    /**
     * Un-ssigns a user to a role
     */
    #[Mutation]
    #[Logged]
    public function unassignUserFromRole(
        string $userGuid,
        int $roleId,
        #[InjectUser] ?User $loggedInUser = null,
    ): bool {
        // Only the Owner can assign permissions
        if (!$this->rolesService->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)) {
            throw new UserError("You don't have permission to assign roles");
        }

        $user = $this->entitiesBuilder->single($userGuid);
        if (!$user instanceof User) {
            throw new UserError("User not found");
        }
        
        $role = $this->rolesService->getRoleById($roleId);

        return $this->rolesService->unassignUserFromRole($user, $role);
    }

    /**
     * Sets a permission for that a role has
     */
    #[Mutation]
    #[Logged]
    public function setRolePermission(
        PermissionsEnum $permission,
        int $roleId,
        bool $enabled = true,
        #[InjectUser] ?User $loggedInUser = null,
    ): Role {
        // Only the Owner can assign permissions
        if (!$this->rolesService->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)) {
            throw new UserError("You don't have permission to assign roles");
        }
        
        $role = $this->rolesService->getRoleById($roleId);

        $newPermissions = $enabled ? [...$role->permissions, $permission] : array_filter($role->permissions, function ($val) use ($permission) {
            return ($val !== $permission);
        });

        $role->permissions = array_unique($newPermissions, flags: SORT_REGULAR);

        $this->rolesService->setRolePermissions($role->permissions, $role, $enabled);

        return $role;
    }

}
