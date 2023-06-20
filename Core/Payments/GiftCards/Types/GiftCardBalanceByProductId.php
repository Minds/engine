<?php
namespace Minds\Core\Payments\GiftCards\Types;

use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;

#[Type()]
class GiftCardBalanceByProductId
{
    public function __construct(
        #[Field] public GiftCardProductIdEnum $productId,
        #[Field] public float $balance
    ) {
    }
}
