<?php

namespace Minds\Helpers;

/**
 * User validation helper methods
 */
class UserValidator
{
    public static function isValidUserId(string $userId): bool
    {
        return is_numeric($userId);
    }
}
