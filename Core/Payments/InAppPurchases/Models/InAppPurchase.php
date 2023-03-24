<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Models;

class InAppPurchase
{
    public function __construct(
        public string $source = "",
        public string $subscriptionId = "",
        public string $purchaseToken = "",
    ) {
    }
}
