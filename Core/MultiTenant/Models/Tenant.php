<?php
namespace Minds\Core\MultiTenant\Models;

use Minds\Core\MultiTenant\Configs\Enums\MultiTenantColorScheme;
use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Tenant
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $id,
        #[Field] public readonly ?string $domain = null,
        #[Field] public readonly ?int $ownerGuid = null,
        #[Field] public readonly ?int $rootUserGuid = null,
        #[Field] public readonly ?MultiTenantConfig $config = null
    ) {
    }

    public static function fromData(array $row): self
    {
        return new self(
            id: $row['tenant_id'],
            domain: $row['domain'],
            ownerGuid: $row['owner_guid'],
            config: new MultiTenantConfig(
                siteName: $row['site_name'] ?? null,
                siteEmail: $row['site_email'] ?? null,
                colorScheme: $row['color_scheme'] ? MultiTenantColorScheme::tryFrom($row['color_scheme']) : null,
                primaryColor: $row['primary_color'] ?? null,
                updatedTimestamp: $row['updated_timestamp'] ? strtotime($row['updated_timestamp']) : null
            )
        );
    }
}
