<?php

namespace Minds\Core\Payments\InAppPurchases\Enums;

enum InAppPurchasePaymentMethodIdsEnum: string
{
    case APPLE = "ios-iap";
    case GOOGLE = "android-iap";
}
