<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * A 'segment' wraps multiple buckets. For example a segments will be a line on a graph, the
 * buckets would be each data point
 */
#[Type]
class AnalyticsChartSegmentType
{
    public function __construct(
        #[Field] public readonly string $label,
        /** @var AnalyticsChartBucketType[] */
        #[Field] public readonly array $buckets,
    ) {
        
    }
}
