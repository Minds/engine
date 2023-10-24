<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Repositories;

use Exception;
use Minds\Core\Data\MySQL\AbstractRepository;
use Selective\Database\Operator;

class TenantUsersRepository extends AbstractRepository
{
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
}
