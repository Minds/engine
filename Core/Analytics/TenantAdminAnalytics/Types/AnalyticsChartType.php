<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use Minds\Core\Analytics\TenantAdminAnalytics\Types\Chart\AnalyticsChartSegmentType;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class AnalyticsChartType
{
    public function __construct(
        #[Field] public readonly AnalyticsMetricEnum $metric,
        /** @var AnalyticsChartSegmentType[] */
        #[Field] public readonly array $segments,
    ) {
        
    }
}
