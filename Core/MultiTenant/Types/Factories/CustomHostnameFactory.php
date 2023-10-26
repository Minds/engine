<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Factories;

use Minds\Core\MultiTenant\Enums\CustomHostnameStatusEnum;
use Minds\Core\MultiTenant\Types\CustomHostname;
use Minds\Core\MultiTenant\Types\CustomHostnameMetadata;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class CustomHostnameFactory
{
    #[Factory(name: 'CustomHostnameInput')]
    public function createCustomHostname(
        string $hostname
    ): CustomHostname {
        return new CustomHostname(
            id: '',
            hostname: $hostname,
            customOriginServer: '',
            status: CustomHostnameStatusEnum::PENDING,
            metadata: new CustomHostnameMetadata([]),
            createdAt: time(),
        );
    }
}
