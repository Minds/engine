<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * The FeedHighlightsEdge contains the FeedHighlightsConnection, which is also a node interface
 */
#[Type]
class FeedHighlightsEdge implements EdgeInterface
{
    public function __construct(protected FeedHighlightsConnection $connection, protected string $cursor)
    {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("feed-highlights-" . Guid::build());
    }

    #[Field]
    public function getType(): string
    {
        return "feed-highlights";
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): FeedHighlightsConnection
    {
        return $this->connection;
    }
}
