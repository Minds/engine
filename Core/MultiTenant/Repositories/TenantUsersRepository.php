<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Enums\TenantUserRoleEnum;
use Minds\Core\MultiTenant\Types\TenantUser;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class TenantUsersRepository extends AbstractRepository
{
    /**
     * Returns the user guids for a tenant
     * Limit is optional
     * @return iterable<int>
     */
    public function getUserGuids(int $tenantId, int $limit = null): iterable
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->columns(['guid'])
            ->from('minds_entities_user')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'));

        if ($limit) {
            $query->limit($limit);
        }

        $stmt = $query->prepare();

        $stmt->execute([
            'tenant_id' => $tenantId,
        ]);

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            yield (int) $row['guid'];
        }
    }

    /**
     * @param int $tenantId
     * @param int $userId
     * @return void
     * @throws Exception
     */
    public function setTenantRootAccount(int $tenantId, int $userId): void
    {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_tenants')
            ->set([
                'root_user_guid' => $userId
            ])
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->execute();

        if (!$statement) {
            throw new Exception('Failed to set tenant root account.');
        }
    }

    /**
     * @param int $tenantId
     * @return TenantUser|null
     * @throws Exception
     */
    public function getTenantRootAccount(int $tenantId): ?TenantUser
    {
        $statement = $this->mysqlClientWriterHandler->select()
            ->from('minds_tenants')
            ->columns([
                'tenant_id',
                'root_user_guid',
                'owner_guid',
                'domain',
            ])
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->execute();

        if ($statement->rowCount() === 0) {
            throw new Exception('Tenant not found.');
        }

        $row = $statement->fetch(PDO::FETCH_ASSOC);

        if (!$row['root_user_guid']) {
            return null;
        }

        return new TenantUser(
            guid: $row['root_user_guid'],
            username: '',
            tenantId: (int) $row['tenant_id'],
            role:  TenantUserRoleEnum::OWNER,
        );
    }
}
