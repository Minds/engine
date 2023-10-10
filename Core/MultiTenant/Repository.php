<?php
namespace Minds\Core\MultiTenant;

use Minds\Core\Data\MySQL\AbstractRepository;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;

class Repository extends AbstractRepository
{
    public function getTenantIdFromDomain(string $domain): ?int
    {
        $query = $this->mysqlClientReaderHandler->select()
            ->from('minds_tenants')
            ->columns(['tenant_id'])
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

        return $rows[0]['tenant_id'];
    }

}
