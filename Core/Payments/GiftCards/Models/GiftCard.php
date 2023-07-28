<?php
namespace Minds\Core\Payments\GiftCards\Models;

use Minds\Core\GraphQL\Traits\GraphQLSubQueryTrait;
use Minds\Core\GraphQL\Types\NodeInterface;
use Minds\Core\Payments\GiftCards\Enums\GiftCardProductIdEnum;
use Minds\Core\Payments\GiftCards\Types\GiftCardTransactionsConnection;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type(name: "GiftCardNode")]
class GiftCard implements NodeInterface
{
    use GraphQLSubQueryTrait;

    public const DEFAULT_GIFT_CARD_PAYMENT_METHOD_ID = "gift_card";

    public function __construct(
        #[Field(outputType: 'String')] public readonly int $guid,
        #[Field] public readonly GiftCardProductIdEnum $productId,
        #[Field] public readonly float $amount,
        #[Field(outputType: 'String')] public readonly int $issuedByGuid,
        #[Field] public readonly int $issuedAt,
        public readonly string $claimCode,
        #[Field] public readonly int $expiresAt,
        #[Field(outputType: 'String')] public ?int $claimedByGuid = null,
        #[Field] public ?int $claimedAt = null,
        #[Field] public float $balance = 0.00,
    ) {
    }

    /**
     * An ID for Graphql
     */
    public function getId(): ID
    {
        return new ID('gift-card-' . $this->guid);
    }

    public function setClaimedByGuid(int $claimedByGuid): self
    {
        $this->claimedByGuid = $claimedByGuid;
        return $this;
    }

    public function setClaimedAt(int $claimedAt): self
    {
        $this->claimedAt = $claimedAt;
        return $this;
    }

    /**
     * True if the gift card has already been claimed
     */
    public function isClaimed(): bool
    {
        return !!$this->claimedByGuid;
    }

    /**
     * Returns transactions relating to the gift card
     * TODO: Find a way to make this not part of the data model
     */
    #[Field]
    public function getTransactions(
        ?int $first = null,
        ?string $after = null,
        ?int $last = null,
        ?string $before = null,
    ): GiftCardTransactionsConnection {
        return $this->gqlQueryRef->giftCardTransactions(
            giftCard: $this,
            first: $first,
            after: $after,
            last: $last,
            before: $before,
            loggedInUser: $this->loggedInUser
        );
    }
}
