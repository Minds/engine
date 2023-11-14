<?php
namespace Minds\Core\Security\Rbac\Services;

use Minds\Core\Config\Config;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Repository;
use Minds\Entities\User;

class RolesService
{
    public function __construct(
        private readonly Config $config,
        private readonly Repository $repository,
    ) {
        
    }

    public function getAllRoles(): array
    {

        return $this->buildRoles();
    }

    
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
            $roles = $this->repository->getUserRoles($user->getGuid());
        } else {
            // Host site, all users will have the default role
            $allRoles = $this->getAllRoles();
            $roles = [
                RolesEnum::DEFAULT->value => $allRoles[RolesEnum::DEFAULT->value],
            ];
            if ($user->isAdmin()) {
                $roles[RolesEnum::ADMIN->value] = $allRoles[RolesEnum::ADMIN->value];
            }
        }
    
        return $roles;
    }

    /**
     * Return a list of all the permission the user has access to, as derived from their role
     * @return string[]
     */
    public function getUserPermissions(User $user): array
    {
        $roles = $this->getRoles($user);
        $permissions = [];

        foreach ($roles as $role) {
            array_push($permissions, ...array_map(function ($permission) {
                return $permission->name;
            }, $role->permissions));
        }

        return array_unique($permissions);
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
            foreach ($this->repository->getRoles() as $id => $role) {
                $roles[$id] = $role;
            }
        }

        return $roles;
    }

    private function isMultiTenant(): bool
    {
        return !!$this->config->get('tenant_id');
    }

}
