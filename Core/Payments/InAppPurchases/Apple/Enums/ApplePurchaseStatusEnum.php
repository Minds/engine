<?php

namespace Minds\Core\Payments\InAppPurchases\Apple\Enums;

enum ApplePurchaseStatusEnum: int
{
    case purchasing = 0;
    case purchased = 1;
    case failed = 2;
    case restored = 3;
    case deferred = 4;

    public static function fromCase(string $case): self
    {
        return match ($case) {
            'purchasing' => self::purchasing,
            'purchased' => self::purchased,
            'failed' => self::failed,
            'restored' => self::restored,
            'deferred' => self::deferred,
            default => throw new \InvalidArgumentException("Invalid case: $case"),
        };
    }
}
