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
                'community_guidelines',
                'expo_project_id',
                'updated_timestamp'
            ]);
    }

    private function buildTenantModel(array $row)
    {
        $tenantId = $row['tenant_id'];
        $domain = $row['domain'];
        $tenantOwnerGuid = $row['owner_guid'];
        $rootUserGuid = $row['root_user_guid'];
        $siteName = $row['site_name'] ?? null;
        $siteEmail = $row['site_email'] ?? null;
        $primaryColor = $row['primary_color'] ?? null;
        $colorScheme = $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : null;
        $communityGuidelines = $row['community_guidelines'] ?? null;
        $expoProjectId = $row['expo_project_id'] ?? null;
        $updatedTimestamp = $row['updated_timestamp'] ?? null;

        return new Tenant(
            id: $tenantId,
            domain: $domain,
            ownerGuid: $tenantOwnerGuid,
            rootUserGuid: $rootUserGuid,
            config: new MultiTenantConfig(
                siteName: $siteName,
                siteEmail: $siteEmail,
                primaryColor: $primaryColor,
                colorScheme: $colorScheme,
                communityGuidelines: $communityGuidelines,
                expoProjectId: $expoProjectId,
                updatedTimestamp: $updatedTimestamp ? strtotime($updatedTimestamp) : null
            )
        );
    }

    public function getTenantFromId(int $id): ?Tenant
    {
        return $this->getTenantFromHash(md5($id));
    }
}
