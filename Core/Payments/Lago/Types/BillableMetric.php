<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Payments\Lago\Enums\ChargeModelEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class BillableMetric
{
    public function __construct(
        #[Field] public readonly string $code,
        #[Field] public readonly ChargeModelEnum $chargeModel,
        #[Field] public readonly int $createdAt,
        #[Field] public readonly int $billableMetricId,
        
    ) {
    }
}
