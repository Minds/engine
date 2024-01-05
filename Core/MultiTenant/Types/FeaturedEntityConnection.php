<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types;

use Minds\Core\GraphQL\Types\Connection;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Extends the Connection Type, but is also a NodeInterface, so can be included in other Connections
 */
#[Type]
class FeaturedEntityConnection extends Connection implements NodeInterface
{
    /** @var FeaturedEntityEdge[] - array of edges */
    protected array $edges = [];

    /**
     * ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("featured-entity-connection-" . Guid::build());
    }

    /**
     * Gets connections edges.
     * @return FeaturedEntityEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
