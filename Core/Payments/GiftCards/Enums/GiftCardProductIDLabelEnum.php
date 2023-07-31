<?php

namespace Minds\Core\Payments\GiftCards\Enums;

enum GiftCardProductIDLabelEnum: string
{
    case BOOST = 'Boost';
    case PLUS = 'Plus';
    case PRO = 'Pro';
    case SUPERMIND = 'Supermind';

    public static function fromProductIdEnum(GiftCardProductIdEnum $productId): self
    {
        return match ($productId) {
            GiftCardProductIdEnum::BOOST => self::BOOST,
            GiftCardProductIdEnum::PLUS => self::PLUS,
            GiftCardProductIdEnum::PRO => self::PRO,
            GiftCardProductIdEnum::SUPERMIND => self::SUPERMIND,
        };
    }
}
