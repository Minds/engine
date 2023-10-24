<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\MultiTenant\Configs\Enums\NetworkUserRoleEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class NetworkUser
{
    public function __construct(
        #[Field(outputType: 'String!')] public readonly int $guid,
        #[Field] public readonly string $username,
        #[Field(outputType: 'String!')] public readonly int $tenantId,
        #[Field] public NetworkUserRoleEnum $role = NetworkUserRoleEnum::USER,
        public readonly string $plainPassword = '',
    ) {
    }
}
