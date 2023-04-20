<?php
declare(strict_types=1);

namespace Minds\Core\Payments\V2\Enums;

use Minds\Core\Payments\V2\Exceptions\InvalidPaymentMethodException;

class PaymentMethod
{
    public const CASH = 1;
    public const OFFCHAIN_TOKENS = 2;
    public const ONCHAIN_TOKENS = 3;

    /**
     * @var array A list of valid values for the enum - To be used for validation purposes
     */
    public const VALID = [
        self::CASH,
        self::OFFCHAIN_TOKENS,
        self::ONCHAIN_TOKENS,
    ];

    /**
     * @param int $paymentMethod
     * @return int
     * @throws InvalidPaymentMethodException
     */
    public static function getValidatedPaymentMethod(int $paymentMethod): int
    {
        return match ($paymentMethod) {
            self::CASH => self::CASH,
            self::OFFCHAIN_TOKENS => self::OFFCHAIN_TOKENS,
            self::ONCHAIN_TOKENS => self::ONCHAIN_TOKENS,
            default => throw new InvalidPaymentMethodException()
        };
    }
}
