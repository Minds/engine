<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Types;

use Minds\Core\GraphQL\Types\Connection;
use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Reports\V2\Types\ReportEdge;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Extends the Connection Type.
 */
#[Type]
class ReportsConnection extends Connection
{
    /** @var ReportEdge[] - array of edges */
    protected array $edges = [];

    /**
     * ID for GraphQL.
     * @return ID - ID for GraphQL.
     */
    #[Field]
    public function getId(): ID
    {
        return new ID("report-connection-" . Guid::build());
    }

    /**
     * Gets connections edges.
     * @return EdgeInterface[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
