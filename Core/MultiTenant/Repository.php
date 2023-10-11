<?php
namespace Minds\Core\MultiTenant;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Models\Tenant;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    public function getTenantFromDomain(string $domain): ?Tenant
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenants')
            ->columns(['tenant_id', 'domain'])
            ->where('domain', Operator::EQ, new RawExp(':domain'));
            
        $domain = strtolower($domain);

        $statement = $query->prepare();

        $statement->execute([
            'domain' => $domain
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        $tenantId = $rows[0]['tenant_id'];
        $domain = $rows[0]['domain'];

        return new Tenant($tenantId, $domain);
    }

    public function getTenantFromHash(string $hash): ?Tenant
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenants')
            ->columns(['tenant_id', 'domain'])
            ->where(new RawExp('md5(tenant_id) = :hash'));
            
        $statement = $query->prepare();

        $statement->execute([
            'hash' => $hash
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        $tenantId = $rows[0]['tenant_id'];
        $domain = $rows[0]['domain'];

        return new Tenant($tenantId, $domain);
    }

}
