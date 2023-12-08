<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class AddOnSummary
{
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public readonly string $name,
        #[Field] public int $monthlyFeeCents,
        #[Field] public ?int $oneTimeFeeCents = null,
    ) {
    }
}
