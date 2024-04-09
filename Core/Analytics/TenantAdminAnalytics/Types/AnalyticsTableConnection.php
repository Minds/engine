<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsTableEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Table\AnalyticsTableRowEdge;
use Minds\Core\GraphQL\Types\Connection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class AnalyticsTableConnection extends Connection
{
    public function __construct(
        #[Field] public readonly AnalyticsTableEnum $table,
    ) {
        
    }

    /**
     * @return AnalyticsTableRowEdge[]
     */
    #[Field]
    public function getEdges(): array
    {
        return $this->edges;
    }
}
