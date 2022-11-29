<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Enums;

use Minds\Core\Boost\V3\Exceptions\InvalidBoostTargetLocationException;

class BoostTargetLocation
{
    public const NEWSFEED = 1;
    public const SIDEBAR = 2;

    /**
     * @param int $targetLocation
     * @return string
     * @throws InvalidBoostTargetLocationException
     */
    public static function toString(int $targetLocation): string
    {
        return match ($targetLocation) {
            self::NEWSFEED => "newsfeed",
            self::SIDEBAR => "sidebar",
            default => throw new InvalidBoostTargetLocationException()
        };
    }
}
