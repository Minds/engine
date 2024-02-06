<?php
namespace Minds\Core\Security\Rbac\Services;

use Minds\Core\Config\Config;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Exceptions\RbacNotConfigured;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Repository;
use Minds\Core\Security\Rbac\Types\UserRoleEdge;
use Minds\Entities\User;

class RolesService
{
    public function __construct(
        private readonly Config $config,
        private readonly Repository $repository,
        private readonly EntitiesBuilder $entitiesBuilder,
    ) {

    }

    /**
     * Initialise the default permissions. This is a run once function.
     */
    public function initPermissions(): void
    {

    }

    /**
     * Returns all the roles (and their permissions)
     */
    public function getAllRoles(): array
    {
        return $this->buildRoles();
    }

    /**
     * Returns all the permissions that exist
     */
    public function getAllPermissions(): array
    {
        return PermissionsEnum::cases();
    }

    /**
     * @return Role[]
     */
    public function getRoles(User $user): array
    {
        $roles = [];

        if ($this->isMultiTenant()) {
            try {
                $roles = $this->repository->getUserRoles((int) $user->getGuid());
            } catch (RbacNotConfigured) {
                // Build the roles
                $this->buildRoles();

                // Re-run this function
                return $this->getRoles($user);
            }
        } else {
            // Host site, all users will have the default role
            $allRoles = $this->getAllRoles();
            if ($user->isAdmin()) {
                $roles[] = $allRoles[RolesEnum::ADMIN->value];
            }

            // All users will have a default role
            $roles[] = $allRoles[RolesEnum::DEFAULT->value];
        }

        return $roles;
    }

    /**
     * Returns a role by its id
     *
     */
    public function getRoleById(int $roleId): ?Role
    {
        $roles = $this->buildRoles();
        return $roles[$roleId] ?? null;
    }

    /**
     * Return a list of all the permission the user has access to, as derived from their role
     * @return PermissionsEnum[]
     */
    public function getUserPermissions(User $user): array
    {
        $roles = $this->getRoles($user);
        $permissions = [];

        foreach ($roles as $role) {
            array_push($permissions, ...array_map(function ($permission) {
                return $permission;
            }, $role->permissions));
        }

        return array_values(array_unique($permissions, flags: SORT_REGULAR));
    }

    /**
     * Returns true if the user has a permission (as inherited from their roles)
     */
    public function hasPermission(User $user, PermissionsEnum $permission): bool
    {
        $permissions = $this->getUserPermissions($user);

        return in_array($permission, $permissions, true);
    }

    /**
    * Return a list of all users
    */
    public function getUsersByRole(
        ?int $roleId = null,
        int $limit = 12,
        string &$loadAfter = null,
        bool &$hasMore = null
    ): iterable {
        // First, gather all the roles and their permissions
        $allRoles = $this->buildRoles();

        $offset = 0;

        if ($loadAfter) {
            $offset = (int) base64_decode($loadAfter, true);
        }

        // Run through users matching query and hyrate the roles
        $i = 0;
        $userGuidsAndRoles = iterator_to_array($this->repository->getUsersByRole(
            roleId: $roleId,
            limit: $limit + 1, // Max iteration size
            offset: $offset
        ));

        if (count($userGuidsAndRoles) > $limit) {
            $hasMore = true;
        } else {
            $hasMore = false;
        }

        foreach ($userGuidsAndRoles as $userGuid => $roleIds) {
            $user = $this->entitiesBuilder->single($userGuid);

            if (!$user instanceof User) {
                continue;
            }

            $userRoles = [];

            foreach ($roleIds as $roleId) {
                $userRoles[$roleId] = $allRoles[$roleId];
            }

            $loadAfter = base64_encode(++$offset);

            yield new UserRoleEdge(
                user: $user,
                roles: $userRoles,
                cursor: $loadAfter,
            );

            if (++$i >= $limit) {
                break;
            }
        }

        return;
    }

    /**
     * Assigns a user to a role
     */
    public function assignUserToRole(User $user, Role $role): bool
    {
        if (!$this->isMultiTenant()) {
            return false;
        }

        return $this->repository->assignUserToRole(
            userGuid: (int) $user->getGuid(),
            roleId: $role->id,
        );
    }

    /**
     * Un-assigns a user from a role
     */
    public function unassignUserFromRole(User $user, Role $role): bool
    {
        if (!$this->isMultiTenant()) {
            return false;
        }

        return $this->repository->unassignUserFromRole(
            userGuid: (int) $user->getGuid(),
            roleId: $role->id,
        );
    }

    /**
     * Sets all the permissions a role will have, or wont have
     * @param array<string,bool> $permissionsMap
     */
    public function setRolePermissions(array $permissionsMap, Role $role): bool
    {
        if (!$this->isMultiTenant()) {
            return false;
        }

        return $this->repository->setRolePermissions($permissionsMap, $role->id);
    }

    /**
     * Returns a list of all roles and fetches customised roles if multi tenant
     */
    private function buildRoles(): array
    {
        /**
         * Default roles and permissions
         */
        $roles = [
            RolesEnum::OWNER->value => new Role(
                RolesEnum::OWNER->value,
                RolesEnum::OWNER->name,
                [
                    PermissionsEnum::CAN_CREATE_POST,
                    PermissionsEnum::CAN_COMMENT,
                    PermissionsEnum::CAN_CREATE_GROUP,
                    PermissionsEnum::CAN_UPLOAD_VIDEO,
                    PermissionsEnum::CAN_INTERACT,
                    PermissionsEnum::CAN_BOOST,
                    PermissionsEnum::CAN_USE_RSS_SYNC,
                    PermissionsEnum::CAN_ASSIGN_PERMISSIONS,
                    PermissionsEnum::CAN_MODERATE_CONTENT,
                    PermissionsEnum::CAN_CREATE_PAYWALL,
                ]
            ),
            RolesEnum::ADMIN->value => new Role(
                RolesEnum::ADMIN->value,
                RolesEnum::ADMIN->name,
                [
                    PermissionsEnum::CAN_CREATE_POST,
                    PermissionsEnum::CAN_COMMENT,
                    PermissionsEnum::CAN_CREATE_GROUP,
                    PermissionsEnum::CAN_UPLOAD_VIDEO,
                    PermissionsEnum::CAN_INTERACT,
                    PermissionsEnum::CAN_BOOST,
                    PermissionsEnum::CAN_USE_RSS_SYNC,
                    PermissionsEnum::CAN_MODERATE_CONTENT,
                ]
            ),
            RolesEnum::MODERATOR->value => new Role(
                RolesEnum::MODERATOR->value,
                RolesEnum::MODERATOR->name,
                [
                    PermissionsEnum::CAN_CREATE_POST,
                    PermissionsEnum::CAN_COMMENT,
                    PermissionsEnum::CAN_CREATE_GROUP,
                    PermissionsEnum::CAN_UPLOAD_VIDEO,
                    PermissionsEnum::CAN_INTERACT,
                    PermissionsEnum::CAN_BOOST,
                    PermissionsEnum::CAN_MODERATE_CONTENT,
                ]
            ),
            RolesEnum::VERIFIED->value => new Role(
                RolesEnum::VERIFIED->value,
                RolesEnum::VERIFIED->name,
                [
                    PermissionsEnum::CAN_CREATE_POST,
                    PermissionsEnum::CAN_COMMENT,
                    PermissionsEnum::CAN_CREATE_GROUP,
                    PermissionsEnum::CAN_UPLOAD_VIDEO,
                    PermissionsEnum::CAN_INTERACT,
                    PermissionsEnum::CAN_BOOST,
                ]
            ),
            RolesEnum::DEFAULT->value => new Role(
                RolesEnum::DEFAULT->value,
                RolesEnum::DEFAULT->name,
                [
                    PermissionsEnum::CAN_CREATE_POST,
                    PermissionsEnum::CAN_COMMENT,
                    PermissionsEnum::CAN_CREATE_GROUP,
                    PermissionsEnum::CAN_UPLOAD_VIDEO,
                    PermissionsEnum::CAN_INTERACT,
                    PermissionsEnum::CAN_BOOST,
                ]
            ),
        ];

        /**
         * For tenants we fetch from the database too, and ovewrite the default roles
         */
        if ($this->isMultiTenant()) {
            try {
                foreach ($this->repository->getRoles() as $id => $role) {
                    $roles[$id] = $role;
                }
            } catch (RbacNotConfigured) {
                // If not setup, we will set the default static roles above and save them
                $this->repository->init($roles);

                return $this->buildRoles();
            }
        }

        return $roles;
    }

    private function isMultiTenant(): bool
    {
        return !!$this->config->get('tenant_id');
    }

}
