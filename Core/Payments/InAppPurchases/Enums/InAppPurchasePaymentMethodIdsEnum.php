<?php

namespace Minds\Core\Payments\InAppPurchases\Enums;

enum InAppPurchasePaymentMethodIdsEnum: string
{
    case APPLE = "ios_iap";
    case GOOGLE = "android_iap";
}
