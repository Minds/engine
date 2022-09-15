<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

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
}
