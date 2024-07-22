<?php

declare(strict_types=1);

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\Data\MySQL\AbstractRepository;
use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use Minds\Exceptions\NotFoundException;
use Selective\Database\Operator;
use Selective\Database\RawExp;

/**
 * Repository for multi-tenant configs.
 */
class Repository extends AbstractRepository
{
    /**
     * Get the config for a tenant by tenant id.
     * NOTE: If you are expecting these configs to be set at boot time,
     * you must also add to the MultiTenant/Repository class
     * @param integer $tenantId - tenant id.
     * @throws NotFoundException - if no rows are found.
     * @return MultiTenantConfig - found config.
     */
    public function get(int $tenantId): MultiTenantConfig
    {
        $query = $this->mysqlClientWriterHandler
            ->select()
            ->from('minds_tenant_configs')
            ->where('tenant_id', Operator::EQ, new RawExp(':tenant_id'))
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($query, [
            'tenant_id' => $tenantId,
        ]);

        $query->execute();

        $row = $query->fetch();

        if (!$row || !count($row)) {
            throw new NotFoundException("Tenant config not found for tenant id: {$tenantId}");
        }

        return new MultiTenantConfig(
            siteName: $row['site_name'] ?? null,
            siteEmail: $row['site_email'] ?? null,
            colorScheme: $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : MultiTenantColorScheme::LIGHT,
            primaryColor: $row['primary_color'] ?? '#1b85d6',
            federationDisabled: (bool) $row['federation_disabled'] ?? false,
            replyEmail: $row['reply_email'] ?? null,
            nsfwEnabled: ($row['nsfw_enabled'] ?? 1) === 1,
            boostEnabled: (bool) $row['boost_enabled'] ?? false,
            customHomePageEnabled: (bool) $row['custom_home_page_enabled'] ?? false,
            customHomePageDescription: $row['custom_home_page_description'] ?? null,
            walledGardenEnabled: (bool) $row['walled_garden_enabled'] ?? false,
            digestEmailEnabled: (bool) $row['digest_email_enabled'] !== 0,
            bloomerangApiKey: $row['bloomerang_api_key'] ?? null,
            lastCacheTimestamp: isset($row['last_cache_timestamp']) ? strtotime($row['last_cache_timestamp']) : null,
            updatedTimestamp: isset($row['updated_timestamp']) ? strtotime($row['updated_timestamp']) : null
        );
    }

    /**
     * Upsert tenant config values.
     * @param integer $tenantId - tenant id.
     * @param ?string $siteName - site name.
     * @param ?MultiTenantColorScheme $colorScheme - color scheme.
     * @param ?string $primaryColor - primary color.
     * @param ?string $communityGuidelines - community guidelines.
     * @param ?bool $federationDisabled - federation disabled.
     * @param ?bool $replyEmail - reply-to email address.
     * @param ?bool $nsfwEnabled - nsfw enabled.
     * @param ?bool $boostEnabled - boost enabled.
     * @param ?string $customHomePageEnabled - whether custom home page is enabled.
     * @param ?string $customHomePageDescription - custom home page description.
     * @param ?bool $walledGardenEnabled - whether wallet garden mode is enabled.
     * @param ?bool $digestEmailEnabled - whether digest email is enabled.
     * @param ?int $lastCacheTimestamp - timestamp of last caching.
     * @return bool - true on success.
     */
    public function upsert(
        int $tenantId,
        ?string $siteName = null,
        ?MultiTenantColorScheme $colorScheme = null,
        ?string $primaryColor = null,
        ?bool $federationDisabled = null,
        ?string $replyEmail = null,
        ?bool $nsfwEnabled = null,
        ?bool $boostEnabled = null,
        ?bool $customHomePageEnabled = null,
        ?string $customHomePageDescription = null,
        ?bool $walledGardenEnabled = null,
        ?bool $digestEmailEnabled = null,
        ?int $lastCacheTimestamp = null
    ): bool {
        $boundValues = ['tenant_id' => $tenantId];
        $rawValues = [];

        if ($siteName !== null) {
            $rawValues['site_name'] = new RawExp(':site_name');
            $boundValues['site_name'] = $siteName;
        }

        if ($colorScheme !== null) {
            $rawValues['color_scheme'] = new RawExp(':color_scheme');
            $boundValues['color_scheme'] = $colorScheme->value;
        }

        if ($primaryColor !== null) {
            $rawValues['primary_color'] = new RawExp(':primary_color');
            $boundValues['primary_color'] = $primaryColor;
        }

        if ($federationDisabled !== null) {
            $rawValues['federation_disabled'] = new RawExp(':federation_disabled');
            $boundValues['federation_disabled'] = $federationDisabled;
        }

        if ($replyEmail !== null) {
            $rawValues['reply_email'] = new RawExp(':reply_email');
            $boundValues['reply_email'] = $replyEmail;
        }

        if ($nsfwEnabled !== null) {
            $rawValues['nsfw_enabled'] = new RawExp(':nsfw_enabled');
            // Convert bool to the format mysql is expecting
            $boundValues['nsfw_enabled'] = ($nsfwEnabled === false) ? 0 : 1;
        }

        if ($boostEnabled !== null) {
            $rawValues['boost_enabled'] = new RawExp(':boost_enabled');
            $boundValues['boost_enabled'] = $boostEnabled;
        }

        if ($lastCacheTimestamp !== null) {
            $rawValues['last_cache_timestamp'] = new RawExp(':last_cache_timestamp');
            $boundValues['last_cache_timestamp'] = date('c', $lastCacheTimestamp);
        }

        if ($customHomePageEnabled !== null) {
            $rawValues['custom_home_page_enabled'] = new RawExp(':custom_home_page_enabled');
            $boundValues['custom_home_page_enabled'] = $customHomePageEnabled;
        }

        if ($customHomePageDescription !== null) {
            $rawValues['custom_home_page_description'] = new RawExp(':custom_home_page_description');
            $boundValues['custom_home_page_description'] = $customHomePageDescription;
        }

        if ($walledGardenEnabled !== null) {
            $rawValues['walled_garden_enabled'] = new RawExp(':walled_garden_enabled');
            $boundValues['walled_garden_enabled'] = $walledGardenEnabled;
        }

        if ($digestEmailEnabled !== null) {
            $rawValues['digest_email_enabled'] = new RawExp(':digest_email_enabled');
            $boundValues['digest_email_enabled'] = $digestEmailEnabled;
        }

        $query = $this->mysqlClientWriterHandler
            ->insert()
            ->into('minds_tenant_configs')
            ->set([
                'tenant_id' => new RawExp(':tenant_id'),
                ...$rawValues
            ])
            ->onDuplicateKeyUpdate($rawValues)
            ->prepare();

        $this->mysqlHandler->bindValuesToPreparedStatement($query, $boundValues);

        return $query->execute();
    }
}
