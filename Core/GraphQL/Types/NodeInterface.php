<?php
namespace Minds\Core\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
interface NodeInterface
{
    #[Field]
    public function getId(): ID;
}
