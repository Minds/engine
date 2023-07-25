<?php
namespace Minds\Core\Payments\GiftCards\Enums;

enum GiftCardProductIdEnum: int
{
    case BOOST = 0;
    case PLUS = 1;
    case PRO = 2;
    case SUPERMIND = 3;

    public static function enabledProductIdEnums(): array
    {
        return [
            GiftCardProductIdEnum::BOOST
        ];
    }
}
