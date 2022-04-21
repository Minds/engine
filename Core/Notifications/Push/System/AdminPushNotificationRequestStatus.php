<?php

namespace Minds\Core\Notifications\Push\System;

use UnexpectedValueException;

class AdminPushNotificationRequestStatus
{
    public const PENDING = 0;
    public const IN_PROGRESS = 1;
    public const DONE = 2;
    public const FAILED = 3;

    public static function fromValue(int $status): string
    {
        return match ($status) {
            self::PENDING => "Pending",
            self::IN_PROGRESS => "In Progress",
            self::DONE => "Done",
            self::FAILED => "Failed",
            default => throw new UnexpectedValueException(),
        };
    }
}
