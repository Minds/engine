<?php
namespace Minds\Core\Payments\GiftCards\Models;

use DateTime;
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
        #[Field] public readonly int $createdAt,  // Timestamp of the transaction
        #[Field] public readonly ?int $refundedAt = null,  // Timestamp of the transaction's refund
        public readonly ?DateTime $createdAtWithMilliseconds = null,
        public readonly ?DateTime $refundedAtWithMilliseconds = null
        // #[Field] public readonly ?float $giftCardRunningBalance = null,
    ) {
    }

    public function getId(): ID
    {
        // TODO: Add timestamp as suffix
        return new ID('gift-card-transaction-' . $this->paymentGuid . '-' . $this->giftCardGuid . '-' . $this->createdAt);
    }
}
