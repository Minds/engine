<?php

namespace Minds\Helpers;

use Minds\Exceptions\StringLengthException;

/**
 * Central validator for various string length checks.
 * Usage Examples:
 * - StringLengthValidator::validate('message', $message);
 * - StringLengthValidator::validateMaxAndTrim('message', $message);
 * - StringLengthValidator::limitsToString('message');
 */
class StringLengthValidator
{
    // key => [min, max] - array of min and max lengths for a given key.
    const LENGTHS = [
        'username' => [
            'min' => 4,
            'max' => 50
        ],
        'message' => [
            // e.g. activity message.
            'min' => 0,
            'max' => 20000
        ],
        'description' => [
            // e.g. description on an image or video.
            'min' => 0,
            'max' => 20000
        ],
        'title' => [
            // e.g. title on an image or video.
            'min' => 0,
            'max' => 2000
        ],
        'briefdescription' => [
            // e.g. channel bio.
            'min' => 0,
            'max' => 5000
        ],
    ];
    
    /**
     * Validates a string is above or equal to the min and below or equal to the max bounds for length.
     * @param string $key - name of key for input field.
     * @param string $target - target string to check.
     * @throws StringLengthException - if string length is determined to be invalid.
     * @return boolean - true if string is within defined bounds.
     */
    public static function validate(string $key, string $target = ''): bool
    {
        $stringLength = strlen($target);
        if (!($stringLength <= self::getMax($key) && $stringLength >= self::getMin($key))) {
            throw new StringLengthException(self::limitsToString($key));
        }
        return true;
    }

    /**
     * Validates a string's max length ONLY and trims it appropriately if the max bound is exceeded
     * Adding in an ellipsis after truncating at the maximum length.
     * @param string $key - name of key for input field.
     * @param string $target - target string to check / truncate.
     * @return string truncated string if required, or the original string if within the defined bounds.
     */
    public static function validateMaxAndTrim(string $key, ?string $target): string
    {
        $maxLength = self::getMax($key);
        return strlen($target) > $maxLength ?
            substr($target, 0, $maxLength).'...' :
            $target;
    }

    /**
     * Outputs a string containing the limits for a given key, for consumption in user facing
     * error messages, for example: "Must be between 4 and 50 characters.".
     * @param string $key - key to get limits for.
     * @return string - contains limits as a string for consumption in error messages.
     */
    public static function limitsToString(string $key): string
    {
        return "Must be between ". self::getMin($key) ." and ". self::getMax($key) ." characters.";
    }

    /**
     * Get max bound by key.
     * @param string $key - key to get max bound for.
     * @return int - max bound for key.
     */
    public static function getMax(string $key): int
    {
        return self::LENGTHS[$key]['max'];
    }

    /**
     * Get min bound by key.
     * @param string $key - key to get min bound for.
     * @return int - min bound for key.
     */
    public static function getMin(string $key): int
    {
        return self::LENGTHS[$key]['min'];
    }
}
