<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class TenantInvite
{
    public function __construct(
        #[Field] public int $id,
        #[Field] public string $email,
    ) {
    }
}
