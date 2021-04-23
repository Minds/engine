<?php
/**
 * Common Regex service.
 */

namespace Minds\Common;

class Regex
{
    // @tags
    const AT = '/(?:@)([a-zA-Z0-9_]+)/';
    
    // #tags or $tags
    const HASH_CASH_TAG = '/([^&]|\b|^)[#|\$]([\wÀ-ÿ\u0E00-\u0E7F\u2460-\u9FBB]+)/uim';

    /**
     * Wrapper around preg_match_all for testing.
     */
    public function globalMatch($regex, $string): int
    {
        return preg_match_all($regex, $string);
    }
}
