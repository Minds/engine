<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Models;

class ProductPurchase
{
    public function __construct(
        public readonly string $productId,
        public readonly string $transactionId = "",
        public readonly bool $acknowledged = false,
    ) {
    }
}
