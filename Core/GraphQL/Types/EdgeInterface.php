<?php
namespace Minds\Core\GraphQL\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
interface EdgeInterface
{
    #[Field]
    public function getNode(): ?NodeInterface;

    #[Field]
    public function getCursor(): string;
}
