<?php
namespace Minds\Core\Analytics\PostHog\Models;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class PostHogPerson
{
    public function __construct(
        #[Field] public readonly string $id,
    ) {
        
    }
}
