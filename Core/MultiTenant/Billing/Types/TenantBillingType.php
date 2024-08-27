<?php
namespace Minds\Core\MultiTenant\Billing\Types;

use DateTimeImmutable;
use Minds\Core\MultiTenant\Enums\TenantPlanEnum;
use Minds\Core\Payments\Checkout\Enums\CheckoutTimePeriodEnum;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

#[Type]
class TenantBillingType
{
    public function __construct(
        #[Field] public readonly TenantPlanEnum $plan,
        #[Field] public readonly CheckoutTimePeriodEnum $period,
        #[Field] public readonly bool $isActive = true,
        #[Field] public readonly ?string $manageBillingUrl = null,
        #[Field] public readonly int $nextBillingAmountCents = 0,
        #[Field] public readonly ?DateTimeImmutable $nextBillingDate = null,
        #[Field] public readonly int $previousBillingAmountCents = 0,
        #[Field] public readonly ?DateTimeImmutable $previousBillingDate = null,
    ) {
        
    }

}
