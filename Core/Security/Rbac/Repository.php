<?php
namespace Minds\Core\Security\Rbac;

use Minds\Core\Config\Config;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends AbstractRepository
{
    /** @var Role[] */
    private $rolesCache;

    public function __construct(
        private Config $config,
        ... $args
    ) {
        parent::__construct(...$args);
    }

    /**
     * Returns a list of all roles and their assigned permissions
     * @return Role[]
     */
    public function getRoles(bool $useCache = true): array
    {
        if ($useCache && isset($this->rolesCache)) {
            return $this->rolesCache;
        }

        $query = $this->buildGetRolesQuery();

        $stmt = $query->prepare();

        $stmt->execute();

        $roles = $this->buildRolesFromRows($stmt->fetchAll(PDO::FETCH_ASSOC));
        $this->rolesCache = $roles;

        return $roles;
    }

    /**
     * Returns a list of roles a user has been assigned to
     * @return Role[]
     */
    public function getUserRoles(int $userGuid): array
    {
        $query = $this->buildGetRolesQuery()
             ->leftJoinRaw('minds_role_user_assignments', 'minds_role_permissions.role_id = minds_role_user_assignments.role_id')
             ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
             ->orWhere('minds_role_permissions.role_id', Operator::EQ, RolesEnum::DEFAULT->value);
    
        $stmt = $query->prepare();

        $stmt->execute([
            'user_guid' => $userGuid
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Save to roles cache as we may need for later, if the user isn't in any roles
        $this->rolesCache = $this->buildRolesFromRows($rows);

        $roles = $this->buildRolesFromRows($rows);

        return $roles;
    }

    /**
     * Assigns a user from a role
     */
    public function assignUserToRole(int $userGuid, int $roleId): bool
    {
        $query = $this->mysqlClientWriterHandler->insert()
            ->into('minds_role_user_assignments')
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                'user_guid' => new RawExp(':user_guid'),
                'role_id ' => new RawExp(':role_id'),
            ])
            ->onDuplicateKeyUpdate([
                'user_guid' => new RawExp(':user_guid'),
            ]);

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->config->get('tenant_id'),
            'user_guid' => $userGuid,
            'role_id' => $roleId,
        ]);
    }

    /**
     * Unassigns a user from a role
     */
    public function unassignUserFromRole(int $userGuid, int $roleId): bool
    {
        $query = $this->mysqlClientWriterHandler->delete()
            ->from('minds_role_user_assignments')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('user_guid', Operator::EQ, new RawExp(':user_guid'))
            ->where('role_id', Operator::EQ, new RawExp(':role_id'));

        $stmt = $query->prepare();

        return $stmt->execute([
            'tenant_id' => $this->config->get('tenant_id'),
            'user_guid' => $userGuid,
            'role_id' => $roleId,
        ]);
    }

    /**
     * Sets (and overwrites) permissions of role
     */
    public function setRolePermissions(array $permissions, int $roleId): bool
    {
        $this->beginTransaction();

        // Remove existing permissions
    
        $query = $this->mysqlClientWriterHandler->delete()
            ->from('minds_role_permissions')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('role_id', Operator::EQ, new RawExp(':role_id'));

        $stmt = $query->prepare();

        $success = $stmt->execute([
            'tenant_id' => $this->config->get('tenant_id'),
            'role_id' => $roleId,
        ]);

        if (!$success) {
            $this->rollbackTransaction();
            return false;
        }
    
        // Add the permissions back

        foreach ($permissions as $permission) {
            $query = $this->mysqlClientWriterHandler->insert()
                ->into('minds_role_permissions')
                ->set([
                    'tenant_id' => new RawExp(':tenant_id'),
                    'permission_id' => new RawExp(':permission'),
                    'role_id ' => new RawExp(':role_id'),
                ])
                ->onDuplicateKeyUpdate([
                    'permission_id' => new RawExp(':permission'),
                ]);

            $stmt = $query->prepare();

            $success = $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id'),
                'permission' => $permission->name,
                'role_id' => $roleId,
            ]);

            if (!$success) {
                $this->rollbackTransaction();
                return false;
            }
        }
    
        $this->commitTransaction();
    
        return true;
    }

    private function buildGetRolesQuery(): SelectQuery
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'role_id' => 'minds_role_permissions.role_id',
                'permissions' => new RawExp("GROUP_CONCAT(permission_id)"),
            ])
            ->from('minds_role_permissions')
            ->where('minds_role_permissions.tenant_id', Operator::EQ, (int) $this->config->get('tenant_id'))
            ->groupBy('minds_role_permissions.role_id');

        return $query;
    }

    private function buildRolesFromRows(array $rows): array
    {
        $roles = [];
        foreach ($rows as $row) {
            $permissions = array_map(function ($permissionId) {
                return constant(PermissionsEnum::class . "::$permissionId");
            }, explode(',', $row['permissions']));

            $role = new Role(
                $row['role_id'],
                RolesEnum::tryFrom($row['role_id'])?->name ?: "Custom role " . $row['role_id'],
                $permissions,
            );

            $roles[$role->id] = $role;
        }

        return $roles;
    }
}
