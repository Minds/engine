<?php
namespace Minds\Core\Payments\GiftCards\Models;

class GiftCardTransaction
{
    public function __construct(
        public readonly int $guid,
        public readonly int $giftCardGuid,
        public readonly float $amount,
        public readonly int $createdAt  // Timestamp of the transaction
    ) {
    }
}
