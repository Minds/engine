<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Types\Factories;

use Minds\Core\Payments\Checkout\Types\AddOnSummary;
use Minds\Core\Payments\Checkout\Types\PlanSummary;
use Minds\Core\Payments\Checkout\Types\Summary;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class SummaryInputFactory
{
    /**
     * @param PlanSummary $planSummary
     * @param int $totalMonthlyFeeCents
     * @param int $totalInitialFeeCents
     * @param AddOnSummary[] $addonsSummary
     * @return Summary
     */
    #[Factory(name: 'SummaryInput')]
    public function createSummaryInput(
        PlanSummary $planSummary,
        int $totalMonthlyFeeCents,
        int $totalInitialFeeCents,
        array $addonsSummary = [],
    ): Summary {
        return new Summary(
            planSummary: $planSummary,
            totalMonthlyFeeCents: $totalMonthlyFeeCents,
            totalInitialFeeCents: $totalInitialFeeCents,
            addonsSummary: $addonsSummary,
        );
    }
}
