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
    const HASH_TAG = '/([^&]|\b|^)#([\wÀ-ÿ\u0E00-\u0E7F\u2460-\u9FBB]+)/uim';

    // $tags
    const CASH_TAG = '/([^&]|\b|^)\$([A-Za-z]+)/uim';

    // #tags | $tags
    const HASH_CASH_TAG = '/([^&]|\b|^)#([\wÀ-ÿ\x0E00\x0E7F\x2460-\x9FBB]+)|([^&]|\b|^)\$([A-Za-z]+)/uim';

    /**
     * Wrapper around preg_match_all for testing.
     */
    public function globalMatch($regex, $string): int
    {
        return preg_match_all($regex, $string);
    }
}
