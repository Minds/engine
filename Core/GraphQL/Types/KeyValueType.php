<?php

namespace Minds\Core\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * Key value pair input.
 */
#[Type]
class KeyValueType
{
    public function __construct(
        #[Field] public readonly string $key,
        #[Field] public readonly string $value
    ) {
    }
}
