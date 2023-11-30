<?php
namespace Minds\Core\Security\Rbac\Controllers;

use GraphQL\Error\UserError;
use Minds\Core\EntitiesBuilder;
use Minds\Core\GraphQL\Types\PageInfo;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Core\Security\Rbac\Types\UserRoleConnection;
use Minds\Core\Security\Rbac\Types\UserRoleEdge;
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
     * @return PermissionsEnum[]
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
        ?string $userGuid = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): array {
        if ($userGuid) {
            // Only those with permissions can view someone elses role
            if (!$this->rolesService->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)) {
                throw new UserError("You don't have permission to assign roles");
            }

            $user = $this->entitiesBuilder->single($userGuid);
        } else {
            $user = $loggedInUser;
        }
        return $this->rolesService->getRoles($user);
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
     * Returns users and their roles
     */
    #[Query]
    #[Logged]
    public function getUsersByRole(
        ?int $roleId = null,
        ?int $first = null,
        ?string $after = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): UserRoleConnection {
        // Only the Owner can assign permissions
        if (!$this->rolesService->hasPermission($loggedInUser, PermissionsEnum::CAN_ASSIGN_PERMISSIONS)) {
            throw new UserError("You don't have permission to assign roles");
        }

        $loadAfter = $after ?: 0;
        $hasMore = false;

        $edges = iterator_to_array($this->rolesService->getUsersByRole(
            roleId: $roleId,
            limit: $first ?: 12,
            loadAfter: $loadAfter,
            hasMore: $hasMore,
        ));

        $pageInfo = new PageInfo(
            hasNextPage: $hasMore,
            hasPreviousPage: !$loadAfter,
            startCursor: $after ? $after : null,
            endCursor: $loadAfter,
        );

        $connection = new UserRoleConnection();
        $connection
            ->setEdges($edges)
            ->setPageInfo($pageInfo);

        return $connection;
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

        $this->rolesService->setRolePermissions([ $permission->name => $enabled ], $role);

        return $role;
    }

}
