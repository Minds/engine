<?php
namespace Minds\Core\MultiTenant;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Models\Tenant;
use PDO;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends AbstractRepository
{
    public function getTenantFromDomain(string $domain): ?Tenant
    {
        $query = $this->buildGetTenantQuery()
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

        return $this->buildTenantModel($rows[0]);
    }

    public function getTenantFromHash(string $hash): ?Tenant
    {
        $query = $this->buildGetTenantQuery()
            ->where(new RawExp('md5(minds_tenants.tenant_id) = :hash'));
            
        $statement = $query->prepare();

        $statement->execute([
            'hash' => $hash
        ]);

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return null;
        }

        return $this->buildTenantModel($rows[0]);
    }

    private function buildGetTenantQuery(): SelectQuery
    {
        return $this->mysqlClientReaderHandler->select()
            ->from('minds_tenants')
            ->leftJoin('minds_tenant_configs', 'minds_tenants.tenant_id', Operator::EQ, 'minds_tenant_configs.tenant_id')
            ->columns([
                'minds_tenants.tenant_id',
                'domain',
                'owner_guid',
                'root_user_guid',
                'site_name',
                'site_email',
                'primary_color',
                'color_scheme',
                'updated_timestamp'
            ]);
    }
    private function buildTenantModel(array $row): Tenant
    {
        $tenantId = $row['tenant_id'];
        $domain = $row['domain'];
        $tenantOwnerGuid = $row['owner_guid'];
        $rootUserGuid = $row['root_user_guid'];
        $siteName = $row['site_name'] ?? null;
        $siteEmail = $row['site_email'] ?? null;
        $primaryColor = $row['primary_color'] ?? null;
        $colorScheme = $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : null;
        $updatedTimestamp = $row['updated_timestamp'] ?? null;

        return new Tenant(
            id: $tenantId,
            domain: $domain,
            ownerGuid: $tenantOwnerGuid,
            rootUserGuid: $rootUserGuid,
            config: new MultiTenantConfig(
                siteName: $siteName,
                siteEmail: $siteEmail,
                colorScheme: $colorScheme,
                primaryColor: $primaryColor,
                updatedTimestamp: $updatedTimestamp ? strtotime($updatedTimestamp) : null
            )
        );
    }

    public function getTenantFromId(int $id): ?Tenant
    {
        return $this->getTenantFromHash(md5($id));
    }

    /**
     * @param int $limit
     * @param int $offset
     * @param int|null $ownerGuid
     * @return Tenant[]
     */
    public function getTenants(
        int $limit,
        int $offset,
        ?int $ownerGuid = null
    ): iterable {
        $query = $this->buildGetTenantQuery()
            ->limit($limit)
            ->offset($offset);

        if ($ownerGuid) {
            $query->where('owner_guid', Operator::EQ, $ownerGuid);
        }

        $stmt = $query->execute();

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $tenant) {
            yield $this->buildTenantModel($tenant);
        }
    }

    public function createTenant(Tenant $tenant): Tenant
    {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into('minds_tenants')
            ->set([
                'owner_guid' => $tenant->ownerGuid,
                'domain' => $tenant->domain,
            ])
            ->prepare();
        $statement->execute();

        return new Tenant(
            id: $this->mysqlClientWriter->lastInsertId(),
            domain: $tenant->domain,
            ownerGuid: $tenant->ownerGuid,
            config: $tenant->config
        );
    }
}
