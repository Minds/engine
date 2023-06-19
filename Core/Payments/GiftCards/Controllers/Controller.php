<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Controllers;

use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class Controller
{
    #[Mutation]
    public function createGiftCard(
        int $productIdEnum,
        float $amount
    ): GiftCard {
        return new GiftCard(
            guid: 0,
            productId: GiftCardProductIdEnum::from($productIdEnum),
            amount: $amount,
            issuedByGuid: 0,
            issuedAt: strtotime("now"),
            claimCode: "",
            expiresAt: strtotime("+1 year"),
            claimedByGuid: null,
            claimedAt: null
        );
    }
}
