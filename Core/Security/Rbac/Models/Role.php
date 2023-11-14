<?php
namespace Minds\Core\Security\Rbac\Models;

use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Role
{
    public function __construct(
        #[Field] public readonly int $id,
        #[Field] public readonly string $name,
        /** @var PermissionsEnum[] $permissions */
        #[Field] public readonly array $permissions,
    ) {
    }
}
