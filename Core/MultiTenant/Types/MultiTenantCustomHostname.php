<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\MultiTenantCustomHostnameStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class MultiTenantCustomHostname
{
    /**
     * @param string $id
     * @param string $hostname
     * @param string $customOriginServer
     * @param MultiTenantCustomHostnameStatusEnum $status
     * @param MultiTenantCustomHostnameMetadata $metadata
     * @param int $createdAt
     */
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public readonly string $hostname,
        #[Field] public readonly string $customOriginServer,
        #[Field] public readonly MultiTenantCustomHostnameStatusEnum $status,
        #[Field] public readonly MultiTenantCustomHostnameMetadata $metadata,
        #[Field] public int $createdAt,
    ) {
    }
}
