<?php
namespace Minds\Core\MultiTenant\Types;

use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\ExtendType;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type(name: 'FeaturedEntityInterface')]
interface FeaturedEntityInterface extends NodeInterface
{
}
