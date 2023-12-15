<?php
declare(strict_types=1);

namespace Minds\Core\Payments\InAppPurchases\Apple\Types;

use Minds\Core\Payments\InAppPurchases\Apple\Enums\AppleSubscriptionStatusEnum;

class AppleSubscription
{
    public function __construct(
        public readonly string $originalTransactionId,
        public readonly AppleSubscriptionStatusEnum $subscriptionStatus,
        public readonly string $signedRenewalInfo,
        public readonly string $signedTransactionInfo,
    ) {
    }
}
