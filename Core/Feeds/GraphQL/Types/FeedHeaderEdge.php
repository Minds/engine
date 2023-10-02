<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Edge to indicate that clients should display a feed header, to give
 * users better context of sub-feeds that they are about to view.
 */
#[Type]
class FeedHeaderEdge implements EdgeInterface
{
    public function __construct(
        protected string $text,
        protected string $cursor
    ) {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("feed-header-" . Guid::build());
    }

    #[Field]
    public function getType(): string
    {
        return "feed-header";
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): FeedHeaderNode
    {
        return new FeedHeaderNode($this->text);
    }
}
