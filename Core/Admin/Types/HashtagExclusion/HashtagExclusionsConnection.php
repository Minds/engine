<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Types\HashtagExclusion;

use Minds\Core\GraphQL\Types\Connection;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Extends the Connection Type.
 */
#[Type]
class HashtagExclusionsConnection extends Connection
{
    /** @var HashtagExclusionEdge[] - array of edges */
    protected array $edges = [];

    /**
     * ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("hashtag-exclusion-connection-" . Guid::build());
    }

    /**
     * Gets connections edges.
     * @return HashtagExclusionEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
