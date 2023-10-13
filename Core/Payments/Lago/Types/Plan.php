<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago\Types;

use Minds\Core\Payments\Lago\Enums\PlanBillingIntervalEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Plan
{
    /**
     * @param int $activeSubscriptionsCount
     * @param int $amountCents
     * @param int $amountCurrency
     * @param string $planCodeId
     * @param int $createdAt
     * @param int $draftInvoicesCount
     * @param PlanBillingIntervalEnum $billingInterval
     * @param string $lagoId
     * @param string $name
     * @param bool $payInAdvance
     */
    public function __construct(
        #[Field] public readonly int $activeSubscriptionsCount,
        #[Field] public readonly int $amountCents,
        #[Field] public readonly int $amountCurrency,
        #[Field] public readonly string $planCodeId,
        #[Field] public readonly int $createdAt,
        #[Field] public readonly int $draftInvoicesCount,
        #[Field] public readonly PlanBillingIntervalEnum $billingInterval,
        #[Field] public readonly string $lagoId,
        #[Field] public readonly string $name,
        #[Field] public readonly bool $payInAdvance = false,
        // #[Field] public readonly ?array $charges = null, // TODO: finish adding charges to plan
    ) {
    }
}
