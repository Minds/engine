<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

/**
 * The 'bucket' contains the data for each data point on the chart. ie. A bar or point.
 */
#[Type]
class AnalyticsChartBucketType
{
    public function __construct(
        #[Field] public readonly string $date,
        #[Field] public readonly string $key,
        #[Field] public readonly int $value,
    ) {
        
    }
}
