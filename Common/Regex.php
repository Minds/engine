<?php
/**
 * Common Regex service.
 */

namespace Minds\Common;

class Regex
{
    // @tags
    const AT = '/(?:@)([a-zA-Z0-9_]+)/';
    
    // #tags
    const HASH_TAG = '/([^&]|\b|^)\#([\pL]+)/uim';

    // $tags
    const CASH_TAG = '/([^&]|\b|^)\$(\pL+)/uim';

    // #tags | $tags
    const HASH_CASH_TAG = '/([^&]|\b|^)\#([\pL\pN]+)|([^&]|\b|^)(\$)([\pL]+)/uim';

    /**
     * Wrapper around preg_match_all for testing.
     */
    public function globalMatch($regex, $string): int
    {
        return preg_match_all($regex, $string);
    }
}
