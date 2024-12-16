<?php

namespace Minds\Core\MultiTenant;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Core\Queue\Runners\WelcomeEmail;
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
                'minds_tenants.stripe_subscription',
                'minds_tenants.trial_start_timestamp',
                'minds_tenants.suspended_timestamp',
                'minds_tenants.deleted_timestamp',
                'minds_tenants_domain_details.domain',
                'owner_guid',
                'root_user_guid',
                'site_name',
                'site_email',
                'primary_color',
                'color_scheme',
                'custom_script',
                'federation_disabled',
                'boost_enabled',
                'reply_email',
                'custom_home_page_enabled',
                'custom_home_page_description',
                'walled_garden_enabled',
                'digest_email_enabled',
                'welcome_email_enabled',
                'logged_in_landing_page_id_web',
                'logged_in_landing_page_id_mobile',
                'is_non_profit',
                'members_only_mode_enabled',
                'last_cache_timestamp',
                'updated_timestamp',
                'nsfw_enabled',
                'bloomerang_api_key',
            ])
            ->where('deleted_timestamp', Operator::IS, null);
    }

    private function buildTenantModel(array $row): Tenant
    {
        $tenantId = $row['tenant_id'];
        $plan = $row['plan'];
        $stripeSubscription = $row['stripe_subscription'];
        $domain = $row['domain'];
        $tenantOwnerGuid = $row['owner_guid'];
        $rootUserGuid = $row['root_user_guid'];
        $siteName = $row['site_name'] ?? null;
        $siteEmail = $row['site_email'] ?? null;
        $primaryColor = $row['primary_color'] ?? '#1b85d6';
        $colorScheme = $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : MultiTenantColorScheme::LIGHT;
        $customScript = isset($row['custom_script']) ? htmlspecialchars_decode($row['custom_script']): null;
        $federationDisabled = (bool) $row['federation_disabled'] ?? false;
        $boostEnabled = $row['boost_enabled'] ?? false;
        $replyEmail = $row['reply_email'] ?? null;
        $customHomePageEnabled = $row['custom_home_page_enabled'] ?? false;
        $customHomePageDescription = $row['custom_home_page_description'] ?? null;
        $walledGardenEnabled = $row['walled_garden_enabled'] ?? false;
        $digestEmailEnabled = $row['digest_email_enabled'] ?? true;
        $welcomeEmailEnabled = $row['welcome_email_enabled'] ?? true;
        $loggedInLandingPageIdWeb = $row['logged_in_landing_page_id_web'] ?? null;
        $loggedInLandingPageIdMobile = $row['logged_in_landing_page_id_mobile'] ?? null;
        $isNonProfit = $row['is_non_profit'] ?? false;
        $membersOnlyModeEnabled = $row['members_only_mode_enabled'] ?? false;
        $updatedTimestamp = $row['updated_timestamp'] ?? null;
        $lastCacheTimestamp = $row['last_cache_timestamp'] ?? null;
        $nsfwEnabled = $row['nsfw_enabled'] ?? true;
        $trialStartTimestamp = $row['trial_start_timestamp'] ?? null;
        $suspendedTimestamp = $row['suspended_timestamp'] ?? null;
        $deletedTimestamp = $row['deleted_timestamp'] ?? null;
        $bloomerangApiKey = $row['bloomerang_api_key'] ?? null;

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
                customScript: $customScript,
                federationDisabled: $federationDisabled,
                boostEnabled: $boostEnabled,
                replyEmail: $replyEmail,
                nsfwEnabled: $nsfwEnabled,
                customHomePageEnabled: $customHomePageEnabled,
                customHomePageDescription: $customHomePageDescription,
                walledGardenEnabled: $suspendedTimestamp ? true : $walledGardenEnabled, // suspended state will always be walled garden
                digestEmailEnabled: $digestEmailEnabled,
                welcomeEmailEnabled: $welcomeEmailEnabled,
                loggedInLandingPageIdWeb: $loggedInLandingPageIdWeb,
                loggedInLandingPageIdMobile: $loggedInLandingPageIdMobile,
                isNonProfit: $isNonProfit,
                membersOnlyModeEnabled: $membersOnlyModeEnabled,
                updatedTimestamp: $updatedTimestamp ? strtotime($updatedTimestamp) : null,
                lastCacheTimestamp: $lastCacheTimestamp ? strtotime($lastCacheTimestamp) : null,
                bloomerangApiKey: $bloomerangApiKey,
            ),
            plan: TenantPlanEnum::fromString($plan),
            trialStartTimestamp: $trialStartTimestamp ? strtotime($trialStartTimestamp) : null,
            suspendedTimestamp: $suspendedTimestamp ? strtotime($suspendedTimestamp) : null,
            deletedTimestamp: $deletedTimestamp ? strtotime($deletedTimestamp) : null,
            stripeSubscription: $stripeSubscription,
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
                'stripe_subscription' => new RawExp(':stripe_subscription'),
            ])
            ->prepare();

        $statement->execute([
            'stripe_subscription' => $tenant->stripeSubscription,
        ]);

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

    /**
     * Upgrades a tenant
     */
    public function upgradeTenant(Tenant $tenant, TenantPlanEnum $plan, string $stripeSubscription): Tenant
    {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_tenants')
            ->set([
                'plan' => $plan->name,
                'trial_start_timestamp' => null,
                'stripe_subscription' => new RawExp(':stripe_subscription'),
            ])
            ->where('tenant_id', Operator::EQ, $tenant->id)
            ->prepare();

        $statement->execute([
            'stripe_subscription' => $stripeSubscription
        ]);

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
    
    /**
     * Returns all expired trials that are not suspended
     */
    public function getExpiredTrialsTenants(): array
    {
        $query = $this->buildGetTenantQuery()
            ->where('trial_start_timestamp', Operator::LT, new RawExp('CURRENT_TIMESTAMP - INTERVAL ' . Tenant::TRIAL_LENGTH_IN_DAYS . ' DAY'))
            ->where('suspended_timestamp', Operator::IS, null);

        $statement = $query->prepare();

        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }

        $tenants = array_map(fn ($row) => $this->buildTenantModel($row), $rows);

        return $tenants;
    }

    /**
     * Returns all suspended tenants that are not soft deleted
     */
    public function getSuspendedTenants(): array
    {
        $query = $this->buildGetTenantQuery()
            ->where('suspended_timestamp', Operator::LT, new RawExp('CURRENT_TIMESTAMP - INTERVAL ' . Tenant::GRACE_PERIOD_BEFORE_DELETION_IN_DAYS . ' DAY'))
            ->where('deleted_timestamp', Operator::IS, null);

        $statement = $query->prepare();

        $statement->execute();

        $rows = $statement->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            return [];
        }

        $tenants = array_map(fn ($row) => $this->buildTenantModel($row), $rows);

        return $tenants;
    }

    /**
     * Set the suspended timestamp for the tenant
     */
    public function suspendTenant(int $tenantId): bool
    {
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_tenants')
            ->set([
                'suspended_timestamp' => date('c'),
            ])
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->prepare();

        return $statement->execute();
    }

    /**
     * Soft delete a tenant and hard delete all its data
     */
    public function deleteTenant(int $tenantId): bool
    {
        $this->beginTransaction();
        $statement = $this->mysqlClientWriterHandler->update()
            ->table('minds_tenants')
            ->set([
                'deleted_timestamp' => date('c'),
            ])
            ->where('tenant_id', Operator::EQ, $tenantId)
            ->prepare();
        $statement->execute();

        $tables = [
            'boost_rankings',
            'boost_summaries',
            'boosts',
            'minds_personal_api_key_scopes',
            'minds_personal_api_keys',
            'minds_chat_rich_embeds',
            'minds_chat_room_member_settings',
            'minds_chat_receipts',
            'minds_chat_members',
            'minds_chat_messages',
            'minds_chat_rooms',
            'minds_user_rss_imports',
            'minds_custom_navigation',
            'minds_payments_config',
            'minds_site_membership_entities',
            'minds_stripe_keys',
            'minds_site_membership_subscriptions',
            'minds_site_membership_tiers_group_assignments',
            'minds_site_membership_tiers_role_assignments',
            'minds_site_membership_tiers',
            'minds_tenant_mobile_configs',
            'minds_push_notification_config',
            'minds_custom_pages',
            'minds_post_notification_subscriptions',
            'minds_tenant_invites',
            'minds_oidc_providers',
            'minds_embedded_comments_settings',
            'minds_embedded_comments_activity_map',
            'minds_activitypub_actors',
            'minds_activitypub_uris',
            'minds_activitypub_keys',
            'minds_role_user_assignments',
            'minds_role_permissions',
            'minds_user_rss_feeds',
            'minds_reports',
            'friends',
            'minds_tenant_featured_entities',
            'minds_votes',
            'minds_tenants_domain_details',
            'minds_entities_object_video',
            'minds_entities_object_image',
            'minds_entities_activity',
            'minds_entities_group',
            'minds_entities_user',
            'minds_entities',
            'minds_tenant_configs',
            'minds_asset_storage',
        ];
        foreach ($tables as $table) {
            $statement = $this->mysqlClientWriterHandler->delete()
                ->from($table)
                ->where('tenant_id', Operator::EQ, $tenantId)
                ->prepare();
            $statement->execute();
        }

        $this->commitTransaction();

        return true;
    }
}
