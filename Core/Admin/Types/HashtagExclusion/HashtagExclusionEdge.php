<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Types\HashtagExclusion;

use Minds\Core\GraphQL\Types\EdgeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;
use Minds\Core\Admin\Types\HashtagExclusion\HashtagExclusionNode;

/**
 * Report edge, can be used in a connection.
 */
#[Type]
class HashtagExclusionEdge implements EdgeInterface
{
    public function __construct(
        protected HashtagExclusionNode $node,
        protected ?string $cursor = null
    ) {
    }

    /**
     * Gets ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("hashtag-exclusion-edge-" . md5($this->node->tenantId . $this->node->tag));
    }

    /**
     * Gets type for GraphQL.
     * @return string - type for GraphQL.
     */
    #[Field]
    public function getType(): string
    {
        return "hashtag-exclusion-edge";
    }

    /**
     * Gets cursor for GraphQL.
     * @return string - cursor for GraphQL.
     */
    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

    /**
     * Gets node.
     * @return HashtagExclusionNode|null - node.
     */
    #[Field]
    public function getNode(): ?HashtagExclusionNode
    {
        return $this->node;
    }
}
