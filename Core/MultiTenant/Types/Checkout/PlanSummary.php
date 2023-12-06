<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Checkout;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class PlanSummary
{
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public readonly string $name,
        #[Field] public int $monthlyFeeCents,
        #[Field] public ?int $oneTimeFeeCents = null,
    ) {
    }
}
