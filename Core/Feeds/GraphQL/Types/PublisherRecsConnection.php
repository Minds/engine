<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\Boost\V3\GraphQL\Types\BoostEdge;
use Minds\Core\GraphQL\Types\Connection;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Extends the Connection Type, but is also a NodeInterface, so can be included in other Connections
 */
#[Type]
class PublisherRecsConnection extends Connection implements NodeInterface
{
    /** @var (UserEdge|BoostEdge)[] */
    protected array $edges = [];

    #[Field]
    public function getId(): ID
    {
        return new ID("publisher-recs");
    }

    /**
     * TODO: clean this up to help with typing. Union types wont work due to the following error being outputted
     * `Error: ConnectionInterface.edges expects type "[EdgeInterface!]!" but PublisherRecsConnection.edges provides type "[UnionUserEdgeBoostEdge!]!".`
     * @return EdgeInterface[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
