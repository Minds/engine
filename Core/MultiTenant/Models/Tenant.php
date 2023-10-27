<?php
namespace Minds\Core\MultiTenant\Models;

use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfig;

class Tenant
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $domain = null,
        public readonly ?int $ownerGuid = null,
        public readonly ?int $rootUserGuid = null,
        public readonly ?MultiTenantConfig $config = null
    ) {
        
    }
}
