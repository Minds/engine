<?php

namespace Minds\Core\Boost\V3\Enums;

class BoostTargetSuitability
{
    /** @var int - A boost with this suitability will be displayed to all users */
    public const SAFE = 1;

    /** @var int - A boost with this suitability will be displayed to users who have opted to see only safe content */
    public const CONTROVERSIAL = 2;

    /**
     * @var array A list of valid values for the enum - To be used for validation purposes
     */
    public const VALID = [
        self::SAFE,
        self::CONTROVERSIAL,
    ];
}
