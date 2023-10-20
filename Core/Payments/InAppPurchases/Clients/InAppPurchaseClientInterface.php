<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Clients;

use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;

interface InAppPurchaseClientInterface
{
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool;

    public function getTransaction(string $transactionId): mixed;

    public function getSubscription(InAppPurchase $inAppPurchase): mixed;
    public function getInAppPurchaseProductPurchase(InAppPurchase $inAppPurchase): mixed;
}
