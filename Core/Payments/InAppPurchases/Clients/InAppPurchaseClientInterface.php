<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Clients;

use Lcobucci\JWT\UnencryptedToken;
use Minds\Core\Payments\InAppPurchases\Models\InAppPurchase;

interface InAppPurchaseClientInterface
{
    public function acknowledgeSubscription(InAppPurchase $inAppPurchase): bool;

    public function getTransaction(string $transactionId): mixed;

    public function getSubscription(InAppPurchase $inAppPurchase): mixed;

    /**
     * @param string $transactionId
     * @return InAppPurchase
     */
    public function getOriginalSubscriptionDetails(string $transactionId): InAppPurchase;

    public function getInAppPurchaseProductPurchase(InAppPurchase $inAppPurchase): mixed;

    public function decodeSignedPayload(string $payload): UnencryptedToken;
}
