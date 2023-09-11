<?php

namespace Minds\Core\Payments\InAppPurchases\Google\Enums;

use Minds\Exceptions\NotFoundException;

enum GoogleInAppPurchaseProductsEnum: string
{
    case BOOST_1USD_1DAY = "boost.001";
    case BOOST_10USD_1DAY = "boost.010";
    case BOOST_1USD_30DAYS = "boost.030";
    case BOOST_10USD_30DAYS = "boost.300";

    /**
     * @param GoogleInAppPurchaseProductsEnum $productIdEnum
     * @return int[]
     * @throws NotFoundException
     */
    public static function getBoostDurationFromEnum(GoogleInAppPurchaseProductsEnum $productIdEnum): array
    {
        return match ($productIdEnum) {
            GoogleInAppPurchaseProductsEnum::BOOST_1USD_1DAY => [
                'daily_bid' => 1,
                'duration' => 1,
            ],
            GoogleInAppPurchaseProductsEnum::BOOST_1USD_30DAYS => [
                'daily_bid' => 1,
                'duration' => 30,
            ],
            GoogleInAppPurchaseProductsEnum::BOOST_10USD_1DAY => [
                'daily_bid' => 10,
                'duration' => 1,
            ],
            GoogleInAppPurchaseProductsEnum::BOOST_10USD_30DAYS => [
                'daily_bid' => 10,
                'duration' => 30,
            ]
        };
    }
}
