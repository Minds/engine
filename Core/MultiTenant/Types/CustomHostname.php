<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Enums\CustomHostnameStatusEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class CustomHostname
{
    /**
     * @param string $id
     * @param string $hostname
     * @param string $customOriginServer
     * @param CustomHostnameStatusEnum $status
     * @param CustomHostnameMetadata $metadata
     * @param int $createdAt
     */
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public readonly string $hostname,
        #[Field] public readonly string $customOriginServer,
        #[Field] public readonly CustomHostnameStatusEnum $status,
        #[Field] public readonly CustomHostnameMetadata $metadata,
        #[Field] public int $createdAt,
    ) {
    }
}
