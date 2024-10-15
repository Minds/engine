<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Types\HashtagExclusion;

use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Hashtag exclusion node type.
 */
#[Type]
class HashtagExclusionNode implements NodeInterface
{
    public function __construct(
        #[Field(outputType: 'String')] public int $tenantId,
        #[Field(outputType: 'String')] public string $tag,
        #[Field(outputType: 'String')] public int $adminGuid,
        #[Field] public ?int $createdTimestamp = null,
        #[Field] public ?int $updatedTimestamp = null
    ) {
    }

    /**
     * Gets ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("hashtag-exclusion-" . md5($this->tenantId . $this->tag));
    }
}
