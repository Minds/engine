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
            communityGuidelines: $row['community_guidelines'] ?? null,
            expoProjectId: $row['expo_project_id'] ?? null,
            expoAndroidAppCredentialsId: $row['expo_android_app_credentials_id'] ?? null,
            expoIosAppCredentialsId: $row['expo_ios_app_credentials_id'] ?? null,
            expoAndroidAppBuildCredentialsId: $row['expo_android_app_build_credentials_id'] ?? null,
            expoIosAppBuildCredentialsId: $row['expo_ios_app_build_credentials_id'] ?? null,
            updatedTimestamp: strtotime($row['updated_timestamp']) ?? null
        );
    }

    /**
     * Upsert tenant config values.
     * @param integer $tenantId - tenant id.
     * @param ?string $siteName - site name.
     * @param ?MultiTenantColorScheme $colorScheme - color scheme.
     * @param ?string $primaryColor - primary color.
     * @param ?string $communityGuidelines - community guidelines.
     * @return bool - true on success.
     */
    public function upsert(
        int $tenantId,
        ?string $siteName,
        ?MultiTenantColorScheme $colorScheme,
        ?string $primaryColor,
        ?string $communityGuidelines,
        ?string $expoProjectId,
        ?string $androidAppCredentialsId,
        ?string $iosAppCredentialsId,
        ?string $androidAppBuildCredentialsId,
        ?string $iosAppBuildCredentialsId
    ): bool {
        $boundValues = [ 'tenant_id' => $tenantId ];
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

        if ($communityGuidelines !== null) {
            $rawValues['community_guidelines'] = new RawExp(':community_guidelines');
            $boundValues['community_guidelines'] = $communityGuidelines;
        }

        if ($expoProjectId !== null) {
            $rawValues['expo_project_id'] = new RawExp(':expo_project_id');
            $boundValues['expo_project_id'] = $expoProjectId;
        }

        if ($androidAppCredentialsId !== null) {
            $rawValues['expo_android_app_credentials_id'] = new RawExp(':expo_android_app_credentials_id');
            $boundValues['expo_android_app_credentials_id'] = $androidAppCredentialsId;
        }

        if ($iosAppCredentialsId !== null) {
            $rawValues['expo_ios_app_build_credentials_id'] = new RawExp(':expo_ios_app_build_credentials_id');
            $boundValues['expo_ios_app_build_credentials_id'] = $iosAppBuildCredentialsId;
        }

        if ($androidAppBuildCredentialsId !== null) {
            $rawValues['expo_android_app_build_credentials_id'] = new RawExp(':expo_android_app_build_credentials_id');
            $boundValues['expo_android_app_build_credentials_id'] = $androidAppBuildCredentialsId;
        }

        if ($iosAppBuildCredentialsId !== null) {
            $rawValues['expo_ios_app_credentials_id'] = new RawExp(':expo_ios_app_credentials_id');
            $boundValues['expo_ios_app_credentials_id'] = $iosAppCredentialsId;
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
