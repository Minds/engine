<?php
namespace Minds\Core\Boost\V3\Enums;

use Minds\Exceptions\ServerErrorException;

class BoostTargetAudiences
{
    /** @var int - users who opt to see ALL content. They will see safe content too */
    public const CONTROVERSIAL = 2;

    /** @var int - users who only want to see safe content. They will not see open content */
    public const SAFE = 1;

    /**
     * Validates if a correct target audience is provided
     * @param int $targetAudience
     * @return bool
     * @throws ServerErrorException
     */
    public static function validate(int $targetAudience): bool
    {
        if (!in_array($targetAudience, [
            self::CONTROVERSIAL,
            self::SAFE
        ], true)) {
            throw new ServerErrorException("Invalid target audience provided");
        }

        return true;
    }
}
