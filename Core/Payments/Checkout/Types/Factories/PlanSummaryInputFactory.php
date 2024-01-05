<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Types\Factories;

use Minds\Core\Payments\Checkout\Types\PlanSummary;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class PlanSummaryInputFactory
{
    #[Factory(name: 'PlanSummaryInput')]
    public function createPlanSummaryInput(
        string $id,
        string $name,
        int $monthlyFeeCents,
        ?int $oneTimeFeeCents = null,
    ): PlanSummary {
        return new PlanSummary(
            id: $id,
            name: $name,
            monthlyFeeCents: $monthlyFeeCents,
            oneTimeFeeCents: $oneTimeFeeCents,
        );
    }
}
