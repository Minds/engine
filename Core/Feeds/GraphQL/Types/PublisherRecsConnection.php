<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\Boost\V3\GraphQL\Types\BoostEdge;
use Minds\Core\Groups\V2\GraphQL\Types\GroupEdge;
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
    /** @var (UserEdge|BoostEdge|GroupEdge)[] */
    protected array $edges = [];

    #[Field]
    public bool $dismissible = true;

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

    /**
     * Whether publisher recs are to be dismissible.
     * @param bool $dismissible - whether publisher recs should be dismissible.
     * @return self
     */
    public function setDismissible(bool $dismissible): self
    {
        $this->dismissible = $dismissible;
        return $this;
    }
}
