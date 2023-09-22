<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\GraphQL\Types;

use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

/**
 * Connection for Boosts.
 */
#[Type]
class BoostsConnection extends Connection
{
    /**
     * Gets Boost edges in connection.
     * @return BoostEdge[] boost edges.
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
