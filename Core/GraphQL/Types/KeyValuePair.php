<?php
namespace Minds\Core\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

/**
 * Key value pair input.
 */
#[Input()]
class KeyValuePair
{
    public function __construct(
        #[Field] public readonly string $key,
        #[Field] public readonly string $value
    ) {
    }
}
