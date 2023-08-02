<?php
namespace Minds\Core\Payments\GiftCards\Types;

use Minds\Core\GraphQL\Traits\GraphQLSubQueryTrait;
use Minds\Core\Payments\GiftCards\Enums\GiftCardOrderingEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Enums\GiftCardStatusFilterEnum;
use Minds\Core\Payments\GiftCards\Models\GiftCard;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type()]
class GiftCardBalanceByProductId
{
    use GraphQLSubQueryTrait;

    public function __construct(
        #[Field] public GiftCardProductIdEnum $productId,
        #[Field] public float $balance
    ) {
    }

    /**
     * Returns the earliest expiring gift that contributes to this balance.
     * @return ?GiftCard - earliest expiring gift card that contributes to this balance.
     */
    #[Field]
    public function getEarliestExpiringGiftCard(): ?GiftCard
    {
        $cards = $this->gqlQueryRef->giftCards(
            first: 1,
            productId: $this->productId,
            statusFilter: GiftCardStatusFilterEnum::ACTIVE,
            ordering: GiftCardOrderingEnum::EXPIRING_ASC,
            loggedInUser: $this->loggedInUser
        );

        return count($cards->getEdges()) ?
            $cards->getEdges()[0]->getNode() :
            null;
    }
}
