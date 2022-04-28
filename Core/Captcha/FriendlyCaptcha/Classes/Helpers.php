<?php

namespace Minds\Core\Captcha\FriendlyCaptcha\Classes;

/**
 * Helper functions for FriendlyCaptcha.
 */
class Helpers
{
    /**
     * Pads hex.
     * @param string $hexValue - hex value.
     * @param integer $bytes - length.
     * @param $where - pad type.
     * @return string - padded string.
     */
    public static function padHex(string $hexValue, int $bytes, $where = STR_PAD_LEFT): string
    {
        return str_pad($hexValue, $bytes * 2, '0', $where);
    }

    /**
     * Extracts hex bytes from a string at a given offset.
     * @param string $string - string to extract from.
     * @param int $offset - offset to start at.
     * @param int $length - amount of bytes to extract.
     * @return string - extracted bytes.
     */
    public static function extractHexBytes(string $string, int $offset, int $length): string
    {
        return substr($string, $offset * 2, $length * 2);
    }

    /**
     * Take little-endian from hex and convert to dec.
     * @param string $hexValue - hex value to convert.
     * @return integer - converted to dec.
     */
    public static function littleEndianHexToDec(string $hexValue): int
    {
        $bigEndianHex = implode('', array_reverse(str_split($hexValue, 2)));
        return hexdec($bigEndianHex);
    }
}
