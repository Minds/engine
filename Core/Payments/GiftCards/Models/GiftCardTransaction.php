<?php
namespace Minds\Core\Payments\GiftCards\Models;

use Minds\Core\GraphQL\Types\NodeInterface;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Types\ID;

#[Type]
class GiftCardTransaction implements NodeInterface
{
    public function __construct(
        #[Field(outputType: 'String')] public readonly int $paymentGuid,
        #[Field(outputType: 'String')] public readonly int $giftCardGuid,
        #[Field] public readonly float $amount,
        #[Field] public readonly int $createdAt,  // Timestamp of the transaction
        // #[Field] public readonly ?float $giftCardRunningBalance = null,
    ) {
    }

    public function getId(): ID
    {
        return new ID('gift-card-transaction-' . $this->paymentGuid . '-' . $this->giftCardGuid);
    }
}
