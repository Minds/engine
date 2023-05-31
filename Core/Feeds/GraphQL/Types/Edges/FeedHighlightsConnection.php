<?php
namespace Minds\Core\Feeds\GraphQL\Types\Edges;

use Minds\Core\GraphQL\Types\Connection;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Extends the Connection Type, but is also a NodeInterface, so can be included in other Connections
 */
#[Type]
class FeedHighlightsConnection extends Connection implements NodeInterface
{
    #[Field]
    public function getId(): ID
    {
        return new ID("feed-highlights-" . Guid::build());
    }

    /**
     * Explicitly will only return activity edges
     * @return ActivityEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
