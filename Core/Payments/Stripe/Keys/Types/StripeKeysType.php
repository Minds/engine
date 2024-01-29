<?php
namespace Minds\Core\Payments\Stripe\Keys\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class StripeKeysType
{
    public function __construct(
        #[Field] public readonly string $pubKey,
        #[Field] public readonly string $secKey,
    ) {
    }
}
