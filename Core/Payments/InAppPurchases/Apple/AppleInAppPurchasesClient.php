<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Apple;

use Minds\Core\Payments\InAppPurchases\Clients\InAppPurchaseClientInterface;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;

class AppleInAppPurchasesClient implements InAppPurchaseClientInterface
{
    /**
     * TODO
     */
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool
    {
        return false;
    }
}
