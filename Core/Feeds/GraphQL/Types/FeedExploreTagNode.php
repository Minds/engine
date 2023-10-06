<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Node for an explore tag item in feed.
 */
#[Type]
class FeedExploreTagNode implements NodeInterface
{
    public function __construct(
        #[Field] public readonly string $tag
    ) {
    }

    /**
     * An ID for Graphql
     */
    public function getId(): ID
    {
        return new ID('feed-explore-tag-node-' . Guid::build());
    }
}
