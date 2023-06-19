<?php
declare(strict_types=1);

namespace Minds\Core\Payments\GiftCards\Controllers;

use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use Minds\Entities\ValidationError;
use Minds\Entities\ValidationErrorCollection;
use Minds\Exceptions\UserErrorException;
use TheCodingMachine\GraphQLite\Annotations\Mutation;

class Controller
{
    /**
     * @param int $productIdEnum
     * @param float $amount
     * @return GiftCard
     * @throws UserErrorException
     */
    #[Mutation]
    public function createGiftCard(
        int $productIdEnum,
        float $amount
    ): GiftCard {
        return new GiftCard(
            guid: 0,
            productId: GiftCardProductIdEnum::tryFrom($productIdEnum) ?? throw new UserErrorException("An error occurred while validating the ", 400, (new ValidationErrorCollection())->add(new ValidationError("productIdEnum", "The value provided is not a valid one"))),
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
