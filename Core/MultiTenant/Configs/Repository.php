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
            colorScheme: $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : null,
            primaryColor: $row['primary_color'] ?? null,
            federationDisabled: (bool) $row['federation_disabled'] ?? false,
            nsfwEnabled: ($row['nsfw_enabled'] ?? 1) === 1,
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
     * @param ?bool $federationDisabled - federation diabled.
     * @param ?int $lastCacheTimestamp - timestamp of last caching.
     * @return bool - true on success.
     */
    public function upsert(
        int $tenantId,
        ?string $siteName = null,
        ?MultiTenantColorScheme $colorScheme = null,
        ?string $primaryColor = null,
        ?bool $federationDisabled = null,
        ?bool $nsfwEnabled = null,
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

        if ($nsfwEnabled !== null) {
            $rawValues['nsfw_enabled'] = new RawExp(':nsfw_enabled');
            // Convert bool to the format mysql is expecting
            $boundValues['nsfw_enabled'] = ($nsfwEnabled === false) ? 0 : 1;
        }

        if ($lastCacheTimestamp !== null) {
            $rawValues['last_cache_timestamp'] = new RawExp(':last_cache_timestamp');
            $boundValues['last_cache_timestamp'] = date('c', $lastCacheTimestamp);
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
