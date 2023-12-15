<?php

namespace Minds\Core\Payments\InAppPurchases\Enums;

enum InAppPurchaseTypeEnum: int
{
    case CONSUMABLE = 1;
    case SUBSCRIPTION = 2;
}
