<?php
namespace Minds\Core\Payments\GiftCards\Models;

class GiftCardTransaction
{
    public function __construct(
        public readonly int $paymentGuid,
        public readonly int $giftCardGuid,
        public readonly float $amount,
        public readonly int $createdAt,  // Timestamp of the transaction
        public readonly ?float $giftCardRunningBalance = null,
    ) {
    }
}
