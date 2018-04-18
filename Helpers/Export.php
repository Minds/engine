<?php
namespace Minds\Helpers;

use Minds\Core;

/**
 * Helper for exporting API responses
 */
class Export
{
    /**
     * Recursively sanitizes an array of data.
     * @param  array $array
     * @return array
     */
    public static function sanitize($array)
    {
        $return = array();

        foreach ($array as $k => $v) {
            if (is_numeric($v) || is_string($v)) {
                if (strlen((string) $v) < 12) {
                    $return[$k] = $v;
                } else {
                    $return[$k] = (string) $v;
                }
                $return[$k] = htmlspecialchars($return[$k], ENT_NOQUOTES);
                $return[$k] = str_replace('&amp;', '&', $return[$k]);
                $return[$k] = str_replace('&nbsp;', ' ', $return[$k]);
            } elseif (is_bool($v)) {
                $return[$k] = $v;
            } elseif (is_object($v) || is_array($v)) {
                $return[$k] = self::sanitize($v);
            } else {
                $return[$k] = $v;
            }
        }

        return $return;
    }
}
