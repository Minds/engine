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
             ->columns([new RawExp("MAX(minds_role_user_assignments.enabled) as enabled")])
             ->leftJoinRaw('minds_role_user_assignments', 'minds_role_user_assignments.role_id = minds_role_user_assignments.role_id AND minds_role_user_assignments.user_guid = :user_guid');
    
        $stmt = $query->prepare();

        $stmt->execute([
            'user_guid' => $userGuid
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Save to roles cache as we may need for later, if the user isn't in any roles
        $this->rolesCache = $this->buildRolesFromRows($rows);

        $filteredRows = array_filter($rows, function ($row) {
            return $row['enabled'];
        });

        $roles = $this->buildRolesFromRows($filteredRows);

        return $roles;
    }

    private function buildGetRolesQuery(): SelectQuery
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns([
                'role_id' => 'minds_role_permissions.role_id',
                'permissions' => new RawExp("GROUP_CONCAT(permission_id)"),
            ])
            ->from('minds_role_permissions')
            ->where('minds_role_permissions.enabled', Operator::EQ, true)
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
