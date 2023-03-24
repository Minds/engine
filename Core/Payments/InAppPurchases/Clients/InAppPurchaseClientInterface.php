<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Clients;

use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;

interface InAppPurchaseClientInterface
{
    public function acknowledgePurchase(InAppPurchase $inAppPurchase): bool;
}
