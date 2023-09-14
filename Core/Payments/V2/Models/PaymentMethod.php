<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Models;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class PaymentMethod
{
    public function __construct(
        #[Field] public readonly string $id,
        #[Field] public readonly string $name,
        #[Field] public readonly ?float $balance,
    ) {
    }
}
