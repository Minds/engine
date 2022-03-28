<?php

namespace Minds\Helpers;

/**
 * Central validator for various string length checks.
 * Usage Examples:
 * - StringLengthValidator::validate('message', $message);
 * - StringLengthValidator::validateMaxAndTrim('message', $message);
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
     * @return boolean - true if string is within defined bounds.
     */
    public static function validate(string $key, string $target = ''): bool
    {
        $stringLength = strlen($target);
        $lengths = self::LENGTHS[$key];
        return $stringLength <= $lengths['max'] && $stringLength >= $lengths['min'];
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
        $maxLength = self::LENGTHS[$key]['max'];
        return strlen($target) > $maxLength ?
            substr($target, 0, $maxLength).'...' :
            $target;
    }
}
