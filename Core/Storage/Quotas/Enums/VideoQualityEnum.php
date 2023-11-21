<?php
declare(strict_types=1);

namespace Minds\Core\Storage\Quotas\Enums;

use Exception;

/**
 * The video quality enum
 */
enum VideoQualityEnum: string
{
    case SOURCE = "source";
    case P360 = "360";
    case P480 = "480";
    case P720 = "720";
    case P1080 = "1080";

    /**
     * @param string $filename
     * @return self
     * @throws Exception
     */
    public static function fromFilenameSuffix(string $filename): self
    {
        return match (true) {
            str_ends_with($filename, self::SOURCE->value) => self::SOURCE,
            str_ends_with($filename, self::P360->value . ".mp4") => self::P360,
            str_ends_with($filename, self::P480->value . ".mp4") => self::P480,
            str_ends_with($filename, self::P720->value . ".mp4") => self::P720,
            str_ends_with($filename, self::P1080->value . ".mp4") => self::P1080,
            default => throw new Exception("Invalid video quality"),
        };
    }
}
