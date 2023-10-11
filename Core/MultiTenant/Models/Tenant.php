<?php
namespace Minds\Core\MultiTenant\Models;

class Tenant
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $domain = null,
    ) {
        
    }
}
