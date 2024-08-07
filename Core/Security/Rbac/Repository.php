<?php
namespace Minds\Core\Security\Rbac;

use Error;
use Minds\Core\Config\Config;
use Minds\Core\Data\cache\InMemoryCache;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Exceptions\RbacNotConfigured;
use Minds\Core\Security\Rbac\Models\Role;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends AbstractRepository
{
    const CACHE_KEY = 'rbac:roles';

    public function __construct(
        private MultiTenantBootService $multiTenantBootService,
        private InMemoryCache $cache,
        ... $args
    ) {
        parent::__construct(...$args);
    }

    /**
     * @param array<int, Role> $roles
     */
    public function init(array $roles): bool
    {
        $this->beginTransaction();
        foreach ($roles as $roleId => $role) {

            $permissionsMap = [];
            foreach ($role->permissions as $permission) {
                $permissionsMap[$permission->name] = true;
            }

            $success = $this->setRolePermissions($permissionsMap, $roleId, useTransaction: false);

            if (!$success) {
                $this->rollbackTransaction();
                return false;
            }
        }

        $this->commitTransaction();

        return true;
    }

    /**
     * Returns a list of all roles and their assigned permissions
     * @return Role[]
     */
    public function getRoles(bool $useCache = true): array
    {
        if ($useCache && $this->cache->has(self::CACHE_KEY)) {
            return $this->cache->get(self::CACHE_KEY);
        }

        $query = $this->buildGetRolesQuery();

        $stmt = $query->prepare();

        $stmt->execute();

        // If no results, its not been configured correctly
        if ($stmt->rowCount() === 0) {
            throw new RbacNotConfigured();
        }

        $roles = $this->buildRolesFromRows($stmt->fetchAll(PDO::FETCH_ASSOC));

        $this->cache->set(self::CACHE_KEY, $roles);

        return $roles;
    }

    /**
     * Returns a list of roles a user has been assigned to
     * @return Role[]
     */
    public function getUserRoles(int $userGuid): array
    {
        $query = $this->buildGetRolesQuery()
            ->leftJoinRaw('minds_role_user_assignments', 'minds_role_permissions.role_id = minds_role_user_assignments.role_id 
                AND minds_role_permissions.tenant_id = minds_role_user_assignments.tenant_id
                AND minds_role_user_assignments.user_guid = :user_guid1')
            ->leftJoin(
                function (SelectQuery $subQuery): void {
                    $subQuery
                        ->columns([
                            'role_id'
                        ])
                        ->from(new RawExp('minds_site_membership_tiers_role_assignments r'))
                        ->innerJoin(['s' => 'minds_site_membership_subscriptions'], 's.membership_tier_guid', Operator::EQ, 'r.membership_tier_guid')
                        ->where('s.user_guid', Operator::EQ, new RawExp(':user_guid2'))
                        ->where('s.valid_to', Operator::GTE, new RawExp('CURRENT_TIMESTAMP()'))
                        ->groupBy('role_id')
                        ->alias('mra');
                },
                "mra.role_id",
                Operator::EQ,
                "minds_role_permissions.role_id"
            );

        $where = "user_guid = :user_guid";

        $where .= " OR minds_role_permissions.role_id = " . RolesEnum::DEFAULT->value;

        $where .= " OR mra.role_id IS NOT NULL";

        //  If the tenant root user is the requested user, they will always be an owner
        if ($userGuid === $this->multiTenantBootService->getTenant()->rootUserGuid) {
            $where .= " OR minds_role_permissions.role_id = " . RolesEnum::OWNER->value;
        }

        $query->whereRaw("($where)");
        
        $stmt = $query->prepare();

        $stmt->execute([
            'user_guid' => $userGuid,
            'user_guid1' => $userGuid,
            'user_guid2' => $userGuid
        ]);

        // There will always be at least one role that is returned, the default role.
        // If none are returned, then we are not configured correctly
        if ($stmt->rowCount() === 0) {
            throw new RbacNotConfigured();
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $roles = $this->buildRolesFromRows($rows);

        return $roles;
    }

    /**
     * Return a list of all users
     * @return iterable<int,int[]>
     */
    public function getUsersByRole(
        ?int $roleId = null,
        ?string $username = null,
        int $limit = 12,
        int $offset = 0,
    ): iterable {
        $values = [
            'tenant_id' => $this->config->get('tenant_id'),
        ];

        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'user_guid' => 'minds_entities_user.guid',
                'role_ids' => new RawExp("GROUP_CONCAT(role_id)"),
            ])
            ->from('minds_entities_user')
            ->innerJoin('minds_entities', 'minds_entities_user.guid', Operator::EQ, 'minds_entities.guid')
            ->leftJoin('minds_role_user_assignments', 'minds_entities_user.guid', Operator::EQ, 'minds_role_user_assignments.user_guid')
            ->where('minds_entities.tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->where('minds_entities_user.source', Operator::EQ, 'local')
            ->groupBy('minds_entities_user.guid')
            ->orderBy("minds_entities_user.guid ASC")
            ->limit($limit)
            ->offset($offset);

        if ($username) {
            $query->where(function (SelectQuery $query) {
                $query->where('minds_entities_user.username', Operator::LIKE, new RawExp(':username'));
                $query->orWhere('minds_entities_user.name', Operator::LIKE, new RawExp(':name'));
            });
            $values['username'] = "%$username%";
            $values['name'] = "%$username%";
        }

        if ($roleId !== null) {
            switch (RolesEnum::tryFrom($roleId)) {
                case RolesEnum::DEFAULT:
                    // If default role selected, include all users (as everyone has the default role)
                    // noop
                    break;
                case RolesEnum::OWNER:
                    // If owner role, we do a query for that role and the root user
                    $query->whereRaw("(minds_role_user_assignments.role_id = " . RolesEnum::OWNER->value . " OR minds_entities_user.guid = :root_user_guid)");
                    $values['root_user_guid'] = $this->multiTenantBootService->getTenant()->rootUserGuid;
                    break;
                default:
                    $query->where('role_id', Operator::EQ, new RawExp(':role_id'));
                    $values['role_id'] = $roleId;
                    break;
            }
        }



        $stmt = $query->prepare();

        $stmt->execute($values);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $row) {
            $roleIds = isset($row['role_ids']) ? array_map('intval', explode(',', $row['role_ids'])) : [];
            
            // If no roles, User will always have the default role
            if (empty($roleIds)) {
                $roleIds[] = RolesEnum::DEFAULT->value;
            }

            // Site owner will always have the owner role
            if (
                (int) $row['user_guid'] === $this->multiTenantBootService->getTenant()->rootUserGuid
                && !in_array(RolesEnum::OWNER->value, $roleIds, true)
            ) {
                $roleIds[] = RolesEnum::OWNER->value;
            }
            
            yield (int) $row['user_guid'] => $roleIds;
        }
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
     * Only set $useTransaction to false if a transaction is used by a calling function
     * @param array<string,bool> $permissionsMap
     */
    public function setRolePermissions(array $permissionsMap, int $roleId, bool $useTransaction = true): bool
    {
        if ($useTransaction) {
            $this->beginTransaction();
        }

        // Add the permissions back

        foreach ($permissionsMap as $permission => $enabled) {
            $query = $this->mysqlClientWriterHandler->insert()
                ->into('minds_role_permissions')
                ->set([
                    'tenant_id' => new RawExp(':tenant_id'),
                    'permission_id' => new RawExp(':permission'),
                    'role_id ' => new RawExp(':role_id'),
                    'enabled' => new RawExp(':enabled'),
                ])
                ->onDuplicateKeyUpdate([
                    'enabled' => new RawExp(':enabled'),
                ]);

            $stmt = $query->prepare();

            $success = $stmt->execute([
                'tenant_id' => $this->config->get('tenant_id'),
                'permission' => $permission,
                'role_id' => $roleId,
                'enabled' => (int) $enabled,
            ]);

            if (!$success) {
                if ($useTransaction) {
                    $this->rollbackTransaction();
                }
                return false;
            }
        }
    
        if ($useTransaction) {
            $this->commitTransaction();
        }
    
        return true;
    }

    private function buildGetRolesQuery(): SelectQuery
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'role_id' => 'minds_role_permissions.role_id',
                'permissions' => new RawExp("GROUP_CONCAT(IF (enabled, permission_id, null))"),
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
            $permissions = $row['permissions'] ? array_filter(array_map(function ($permissionId) {
                try {
                    return constant(PermissionsEnum::class . "::$permissionId");
                } catch (Error) {
                    return null;
                }
            }, explode(',', $row['permissions']))) : [];

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
