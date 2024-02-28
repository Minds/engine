<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types\Table;

use Minds\Core\GraphQL\Types\EdgeInterface;
use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class AnalyticsTableRowEdge implements EdgeInterface
{
    public function __construct(
        protected AnalyticsTableRowNodeInterface $node,
        protected string $cursor = '',
    ) {
    }

    /**
     * @inheritDoc
     */
    #[Field]
    public function getNode(): NodeInterface
    {
        return $this->node;
    }

    /**
     * @inheritDoc
     */
    #[Field]
    public function getCursor(): string
    {
        return $this->cursor;
    }

}
