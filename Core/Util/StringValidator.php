<?php
/**
 * @author: eiennohi.
 */

namespace Minds\Core\Util;

class StringValidator
{
    protected const DOMAIN_REGEX = '/(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9][a-z0-9-]{0,61}[a-z0-9]/';
    protected const HEX_COLOR_REGEX = '/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/';

    public static function isDomain(string $domain): bool
    {
        preg_match(self::DOMAIN_REGEX, $domain, $matches);

        return $matches && $matches[0] === $domain;
    }

    public static function isHexColor(string $hexColor): bool
    {
        preg_match(self::HEX_COLOR_REGEX, $hexColor, $matches);

        return $matches && count($matches) > 0;
    }
}
