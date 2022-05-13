<?php

namespace Minds\Core\Notifications\Push\System;

use UnexpectedValueException;

class AdminPushNotificationRequestStatus
{
    public const PENDING = 0;
    public const IN_PROGRESS = 1;
    public const DONE = 2;
    public const FAILED = 3;

    public static function statusLabelFromValue(int $status): string
    {
        return match ($status) {
            self::PENDING => "Pending",
            self::IN_PROGRESS => "In Progress",
            self::DONE => "Completed",
            self::FAILED => "Failed",
            default => throw new UnexpectedValueException(),
        };
    }

    public static function tryFromValue(int $status): int
    {
        return match ($status) {
            self::PENDING,
            self::IN_PROGRESS,
            self::DONE,
            self::FAILED => $status,
            default => throw new UnexpectedValueException(),
        };
    }
}
