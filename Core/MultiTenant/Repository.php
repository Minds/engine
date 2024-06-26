<?php

namespace Minds\Core\MultiTenant;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use PDO;
use PDOException;
use Selective\Database\Operator;
use Selective\Database\RawExp;
use Selective\Database\SelectQuery;

class Repository extends AbstractRepository
{
    public function getTenantFromDomain(string $domain): ?Tenant
    {
        $query = $this->buildGetTenantQuery()
            ->where('minds_tenants_domain_details.domain', Operator::EQ, new RawExp(':domain'));

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
            ->leftJoin('minds_tenants_domain_details', 'minds_tenants.tenant_id', Operator::EQ, 'minds_tenants_domain_details.tenant_id')
            ->columns([
                'minds_tenants.tenant_id',
                'minds_tenants.plan',
                'minds_tenants.trial_start_timestamp',
                'minds_tenants_domain_details.domain',
                'owner_guid',
                'root_user_guid',
                'site_name',
                'site_email',
                'primary_color',
                'color_scheme',
                'federation_disabled',
                'boost_enabled',
                'reply_email',
                'custom_home_page_enabled',
                'custom_home_page_description',
                'walled_garden_enabled',
                'last_cache_timestamp',
                'updated_timestamp',
                'nsfw_enabled',
            ]);
    }

    private function buildTenantModel(array $row): Tenant
    {
        $tenantId = $row['tenant_id'];
        $plan = $row['plan'];
        $domain = $row['domain'];
        $tenantOwnerGuid = $row['owner_guid'];
        $rootUserGuid = $row['root_user_guid'];
        $siteName = $row['site_name'] ?? null;
        $siteEmail = $row['site_email'] ?? null;
        $primaryColor = $row['primary_color'] ?? null;
        $colorScheme = $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : null;
        $federationDisabled = (bool) $row['federation_disabled'] ?? false;
        $boostEnabled = $row['boost_enabled'] ?? false;
        $replyEmail = $row['reply_email'] ?? null;
        $customHomePageEnabled = $row['custom_home_page_enabled'] ?? false;
        $customHomePageDescription = $row['custom_home_page_description'] ?? null;
        $walledGardenEnabled = $row['walled_garden_enabled'] ?? false;
        $updatedTimestamp = $row['updated_timestamp'] ?? null;
        $lastCacheTimestamp = $row['last_cache_timestamp'] ?? null;
        $nsfwEnabled = $row['nsfw_enabled'] ?? true;
        $trialStartTimestamp = $row['trial_start_timestamp'] ?? null;

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
                federationDisabled: $federationDisabled,
                boostEnabled: $boostEnabled,
                replyEmail: $replyEmail,
                nsfwEnabled: $nsfwEnabled,
                customHomePageEnabled: $customHomePageEnabled,
                customHomePageDescription: $customHomePageDescription,
                walledGardenEnabled: $walledGardenEnabled,
                updatedTimestamp: $updatedTimestamp ? strtotime($updatedTimestamp) : null,
                lastCacheTimestamp: $lastCacheTimestamp ? strtotime($lastCacheTimestamp) : null,
            ),
            plan: TenantPlanEnum::fromString($plan),
            trialStartTimestamp: $trialStartTimestamp ? strtotime($trialStartTimestamp) : null
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
     * @return iterable<Tenant>
     */
    public function getTenants(
        int  $limit,
        int  $offset,
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

    public function createTenant(
        Tenant $tenant,
        bool   $isTrial = false
    ): Tenant {
        $statement = $this->mysqlClientWriterHandler->insert()
            ->into('minds_tenants')
            ->set([
                'owner_guid' => $tenant->ownerGuid,
                'plan' => $tenant->plan->name,
                'trial_start_timestamp' => $isTrial ? date('c', time()) : null,
            ])
            ->prepare();

        $statement->execute();

        return new Tenant(
            id: $this->mysqlClientWriter->lastInsertId(),
            ownerGuid: $tenant->ownerGuid,
            config: $tenant->config,
            plan: $tenant->plan,
            trialStartTimestamp: time()
        );
    }

    /**
     * @param User $user
     * @return bool
     * @throws ServerErrorException
     */
    public function canHaveTrialTenant(
        User $user
    ): bool {
        $query = $this->mysqlClientWriterHandler->select()
            ->from('minds_tenants')
            ->columns([
                new RawExp('COUNT(tenant_id) as count')
            ])
            ->where('owner_guid', Operator::EQ, $user->getGuid());

        try {
            $stmt = $query->execute();

            return ((int)$stmt->fetch(PDO::FETCH_ASSOC)['count']) === 0;
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to check if user has trial tenant', previous: $e);
        }
    }

    /**
     * @param User $user
     * @return Tenant
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function getTrialTenantForOwner(User $user): Tenant
    {
        $stmt = $this->buildGetTenantQuery()
            ->where('owner_guid', Operator::EQ, $user->getGuid())
            ->where('trial_start_timestamp', Operator::IS_NOT, null)
            ->limit(1)
            ->prepare();

        try {
            $stmt->execute();

            if ($stmt->rowCount() === 0) {
                throw new NotFoundException(message: 'Failed to get trial tenant for owner');
            }

            return $this->buildTenantModel($stmt->fetch(PDO::FETCH_ASSOC));
        } catch (PDOException $e) {
            throw new ServerErrorException(message: 'Failed to get trial tenant for owner', previous: $e);
        }
    }

    public function upgradeTrialTenant(Tenant $tenant, TenantPlanEnum $plan): Tenant
    {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_tenants')
            ->set([
                'plan' => $plan->name,
                'trial_start_timestamp' => null,
            ])
            ->where('tenant_id', Operator::EQ, $tenant->id)
            ->prepare();

        $statement->execute();

        return new Tenant(
            id: $tenant->id,
            domain: $tenant->domain,
            ownerGuid: $tenant->ownerGuid,
            rootUserGuid: $tenant->rootUserGuid,
            config: $tenant->config,
            plan: $plan,
            trialStartTimestamp: null
        );
    }
}
