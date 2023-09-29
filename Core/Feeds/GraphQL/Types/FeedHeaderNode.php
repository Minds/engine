<?php
namespace Minds\Core\Feeds\GraphQL\Types;

use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Node for feed headers, giving users better context of sub-feeds
 * that they are about to view.
 */
#[Type]
class FeedHeaderNode implements NodeInterface
{
    public function __construct(
        #[Field] public readonly string $text
    ) {
    }

    /**
     * An ID for Graphql
     */
    public function getId(): ID
    {
        return new ID('feed-header-node-' . Guid::build());
    }
}
