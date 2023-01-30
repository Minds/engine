<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

class BoostPaymentMethod
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
}
