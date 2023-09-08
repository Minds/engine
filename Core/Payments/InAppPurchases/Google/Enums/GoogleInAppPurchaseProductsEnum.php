<?php

namespace Minds\Core\Payments\InAppPurchases\Google\Enums;

use Minds\Exceptions\NotFoundException;

enum GoogleInAppPurchaseProductsEnum: string
{
    case BOOST_1_USD_1DAY = "boost.001";
    // case BOOST_1_USD_1DAY = "boost.001";
    // case BOOST_1_USD_1DAY = "boost.001";
    // case BOOST_1_USD_1DAY = "boost.001";

    /**
     * @param GoogleInAppPurchaseProductsEnum $productIdEnum
     * @return int[]
     * @throws NotFoundException
     */
    public static function getBoostDurationFromEnum(GoogleInAppPurchaseProductsEnum $productIdEnum): array
    {
        return match ($productIdEnum) {
            GoogleInAppPurchaseProductsEnum::BOOST_1_USD_1DAY => [
                'daily_bid' => 1,
                'duration' => 1,
            ],
            default => throw new NotFoundException("Invalid product id"),
        };
    }
}
