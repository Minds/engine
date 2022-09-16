<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Minds\Core\Supermind\Exceptions\SupermindRequestPaymentTypeNotFoundException;

/**
 *
 */
class SupermindRequestPaymentMethod
{
    const CASH = 0;
    const OFFCHAIN_TOKEN = 1;

    const VALID_PAYMENT_METHODS = [
        self::CASH,
        self::OFFCHAIN_TOKEN
    ];

    /**
     * @param int $paymentMethod
     * @return string
     * @throws SupermindRequestPaymentTypeNotFoundException
     */
    public static function getPaymentTypeId(int $paymentMethod): string
    {
        return match ($paymentMethod) {
            self::CASH => 'usd',
            self::OFFCHAIN_TOKEN => 'offchain_token',
            default => throw new SupermindRequestPaymentTypeNotFoundException()
        };
    }
}
