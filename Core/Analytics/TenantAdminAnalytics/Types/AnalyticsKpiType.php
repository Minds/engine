<?php
namespace Minds\Core\Analytics\TenantAdminAnalytics\Types;

use Minds\Core\Analytics\TenantAdminAnalytics\Enums\AnalyticsMetricEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class AnalyticsKpiType
{
    public function __construct(
        #[Field] public readonly AnalyticsMetricEnum $metric,
        #[Field] public readonly int $value,
        #[Field] public readonly int $previousPeriodValue,
    ) {
        
    }
}
