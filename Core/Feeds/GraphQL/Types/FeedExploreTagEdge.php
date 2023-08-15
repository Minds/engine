<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Edge to indicate that clients should display an explore tag item in feed.
 */
#[Type]
class FeedExploreTagEdge implements EdgeInterface
{
    public function __construct(
        protected string $tag,
        protected string $cursor
    ) {
    }

    #[Field]
    public function getId(): ID
    {
        return new ID("feed-explore-tag-edge-" . Guid::build());
    }

    #[Field]
    public function getType(): string
    {
        return "feed-explore-tag";
    }

    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    #[Field]
    public function getNode(): FeedExploreTagNode
    {
        return new FeedExploreTagNode($this->tag);
    }
}
