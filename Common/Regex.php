<?php
/**
 * Common Regex service.
 */

namespace Minds\Common;

class Regex
{
    const AT = '/(?:@)([a-zA-Z0-9_]+)/';

    /**
     * Wrapper around preg_match_all for testing.
     */
    public function globalMatch($regex, $string): int
    {
        return preg_match_all($regex, $string);
    }
}
