<?php

namespace Minds\Core\Payments\InAppPurchases\Apple\Enums;

enum AppleSubscriptionStatusEnum: int
{
    case ACTIVE = 1;
    case EXPIRED = 2;
    case RETRY_BILLING = 3;
    case BILLING_GRACE = 4;
    case REVOKED = 5;
}
