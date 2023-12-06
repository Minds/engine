<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Checkout;

use Minds\Core\MultiTenant\Enums\CheckoutPageKeyEnum;
use Minds\Core\MultiTenant\Enums\CheckoutTimePeriodEnum;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class CheckoutPage
{
    public function __construct(
        #[Field] public readonly CheckoutPageKeyEnum $id,
        #[Field] public readonly string $title,
        #[Field] public readonly ?string $description = null,
        #[Field] public CheckoutTimePeriodEnum $timePeriod = CheckoutTimePeriodEnum::MONTHLY,
        #[Field] public int $totalAnnualSavingsCents = 0,
        #[Field(outputType: 'Plan!')] public ?Plan $plan = null,
        #[Field(outputType: 'Summary!')] public ?Summary $summary = null,
        #[Field] public readonly ?string $termsMarkdown = null,
        public array $addOns = [],
    ) {
    }

    /**
     * @return AddOn[]
     */
    #[Field]
    public function getAddOns(): array
    {
        return $this->addOns;
    }
}
