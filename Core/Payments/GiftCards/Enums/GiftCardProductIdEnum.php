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

    public static function getEnumLabel(self $enum): string
    {
        return match ($enum) {
            GiftCardProductIdEnum::BOOST => "Boost Credits",
            GiftCardProductIdEnum::PLUS => "Minds Plus Credits",
            GiftCardProductIdEnum::PRO => "Minds Pro Credits",
            GiftCardProductIdEnum::SUPERMIND => "Supermind Credits",
        };
    }
}
