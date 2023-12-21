<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Summary
{
    public function __construct(
        #[Field] public readonly PlanSummary $planSummary,
        #[Field] public readonly int $totalMonthlyFeeCents,
        #[Field] public readonly int $totalInitialFeeCents,
        private readonly array $addonsSummary = [],
    ) {
    }

    /**
     * @return AddOn[]
     */
    #[Field]
    public function getAddonsSummary(): array
    {
        return $this->addonsSummary;
    }
}
