<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The PublisherRecsEdge contains the PublisherRecsConnection, which is also a node interface
 */
#[Type]
class PublisherRecsEdge implements EdgeInterface
{
    public function __construct(protected PublisherRecsConnection $connection, protected string $cursor)
    {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("publisher-recs-" . Guid::build());
    }

    #[Field]
    public function getType(): string
    {
        return "publisher-recs";
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): PublisherRecsConnection
    {
        return $this->connection;
    }
}
