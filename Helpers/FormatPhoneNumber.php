<?php

/**
 * Format a phone number by adding a leading +
 */

namespace Minds\Helpers;

class FormatPhoneNumber
{
    /**
     * Formats a phone number by adding a leading '+' character if required.
     * @param string $number - phone number to format.
     * @return string formatted number
     */
    public static function format(string $number): string
    {
        if ($number[0] !== '+') {
            $number = '+'.$number;
        }
        return $number;
    }
}
