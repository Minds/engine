<?php

namespace Minds\Core\Security\RateLimits;

use Minds\Traits\MagicAttributes;

/**
 * @method RateLimit setSeconds(int $seconds)
 * @method RateLimit setMax(int $max)
 * @method RateLimit setKey(string $key)
 */
class RateLimit
{
    use MagicAttributes;

    /** @var string */
    private $key;

    /** @var int */
    private $max;

    /** @var int */
    private $seconds;

    /**
     * utility attribute that is max - count
     * @var int */
    private $remaining;
}
