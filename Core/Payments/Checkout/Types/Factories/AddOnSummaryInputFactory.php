<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Types\Factories;

use Minds\Core\Payments\Checkout\Types\AddOnSummary;
use TheCodingMachine\GraphQLite\Annotations\Factory;

class AddOnSummaryInputFactory
{
    #[Factory(name: 'AddOnSummaryInput')]
    public function createAddOnSummaryInput(
        string $id,
        string $name,
        int $monthlyFeeCents,
        ?int $oneTimeFeeCents = null,
    ): AddOnSummary {
        return new AddOnSummary(
            id: $id,
            name: $name,
            monthlyFeeCents: $monthlyFeeCents,
            oneTimeFeeCents: $oneTimeFeeCents,
        );
    }
}
