<?php
namespace Minds\Core\Payments\GiftCards\Models;

use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class GiftCardTransaction implements NodeInterface
{
    public function __construct(
        #[Field(outputType: 'String')] public readonly int $paymentGuid,
        #[Field(outputType: 'String')] public readonly int $giftCardGuid,
        #[Field] public readonly float $amount,
        #[Field] public readonly int $createdAt, // Timestamp of the transaction.
        #[Field] public readonly ?int $refundedAt = null,  // Timestamp of the transaction's refund.
        #[Field] public ?string $boostGuid = null, // guid of a linked boost, if there is one.
        #[Field] public readonly ?string $giftCardIssuerGuid = null, // guid of the gift card issuer.
        #[Field] public readonly ?string $giftCardIssuerName = null // name of the gift card issuer.
    ) {
    }

    public function getId(): ID
    {
        return new ID('gift-card-transaction-' . $this->paymentGuid . '-' . $this->giftCardGuid);
    }
}
