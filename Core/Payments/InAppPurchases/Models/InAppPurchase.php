<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Models;

use Minds\Entities\User;

class InAppPurchase
{
    public function __construct(
        public string $source = "",
        public string $subscriptionId = "",
        public string $purchaseToken = "",
        public ?User $user = null,
    ) {
    }
}
