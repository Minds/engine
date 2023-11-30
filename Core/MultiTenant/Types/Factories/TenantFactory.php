<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Factories;

use Minds\Core\MultiTenant\Configs\Models\MultiTenantConfigInput;
use Minds\Core\MultiTenant\Models\Tenant;
use Minds\Entities\User;
use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;

class TenantFactory
{
    #[Factory(name: 'TenantInput')]
    public function createTenant(
        ?string $domain = null,
        ?int $ownerGuid = null,
        ?MultiTenantConfigInput $config = null,
        #[InjectUser] ?User $loggedInUser = null,
    ): Tenant {
        return new Tenant(
            id: 0,
            domain: $domain,
            ownerGuid: $ownerGuid ?? (int) $loggedInUser->getGuid(),
            config: $config,
        );
    }
}
