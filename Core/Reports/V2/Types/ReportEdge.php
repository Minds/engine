<?php
declare(strict_types=1);

namespace Minds\Core\Reports\V2\Types;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\Guid;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

/**
 * Report edge, can be used in a connection.
 */
#[Type]
class ReportEdge implements EdgeInterface
{
    public function __construct(
        protected Report $node,
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
        return new ID("report-edge-" . $this->node->reportGuid);
    }

    /**
     * Gets type for GraphQL.
     * @return string - type for GraphQL.
     */
    #[Field]
    public function getType(): string
    {
        return "report-edge";
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
     * @return Report - node.
     */
    #[Field]
    public function getNode(): ?Report
    {
        return $this->node;
    }
}
