<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Types\Checkout;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Plan
{
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public readonly string $name,
        #[Field] public readonly string $description,
        #[Field] public readonly string $perksTitle,
        private readonly array $perks,
        #[Field(outputType: "Int!")] public ?int $monthlyFeeCents = null,
        #[Field] public ?int $oneTimeFeeCents = null,
    ) {
    }

    /**
     * @return string[]
     */
    #[Field]
    public function getPerks(): array
    {
        return $this->perks;
    }
}
