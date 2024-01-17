<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Guid;
use Minds\Core\MultiTenant\Types\FeaturedGroup;
use Minds\Core\MultiTenant\Types\FeaturedUser;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Featured entity edge, can be used in a connection.
 */
#[Type]
class FeaturedEntityEdge implements EdgeInterface
{
    public function __construct(
        protected FeaturedUser|FeaturedGroup $node,
        protected ?string $cursor = null
    ) {
    }

    /**
     * Gets ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("featured-entity-" . Guid::build());
    }

    /**
     * Gets type for GraphQL.
     * @return string - type for GraphQL.
     */
    #[Field]
    public function getType(): string
    {
        return "featured-entity";
    }

    /**
     * Gets cursor for GraphQL.
     * @return string - cursor for GraphQL.
     */
    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    /**
     * Gets node - can be either a FeaturedUser or FeaturedGroup.
     */
    #[Field]
    public function getNode(): NodeInterface
    {
        return $this->node;
    }
}
