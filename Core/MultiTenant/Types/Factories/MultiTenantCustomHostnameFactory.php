<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Factories;

use Minds\Core\MultiTenant\Enums\MultiTenantCustomHostnameStatusEnum;
use Minds\Core\MultiTenant\Types\MultiTenantCustomHostname;
use Minds\Core\MultiTenant\Types\MultiTenantCustomHostnameMetadata;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class MultiTenantCustomHostnameFactory
{
    #[Factory(name: 'MultiTenantCustomHostnameInput')]
    public function createMultiTenantCustomHostname(
        string $hostname
    ): MultiTenantCustomHostname {
        return new MultiTenantCustomHostname(
            id: '',
            hostname: $hostname,
            customOriginServer: '',
            status: MultiTenantCustomHostnameStatusEnum::PENDING,
            metadata: new MultiTenantCustomHostnameMetadata([]),
            createdAt: time(),
        );
    }
}
