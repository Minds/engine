<?php
/**
 * Canary
 *
 * @author edgebal
 */

namespace Minds\Core\Features;

use Minds\Common\Cookie;

/**
 * Controls Canary cookie setting
 * @package Minds\Core\Features
 */
class Canary
{
    /** @var Cookie $cookie */
    protected $cookie;

    /**
     * Canary constructor.
     * @param Cookie $cookie
     */
    public function __construct(
        $cookie = null
    ) {
        $this->cookie = $cookie ?: new Cookie();
    }

    /**
     * Sets canary cookie value
     * @param bool $enabled
     * @return bool
     */
    public function setCookie(bool $enabled): bool
    {
        $this->cookie
            ->setName('canary')
            ->setValue((int) $enabled)
            ->setExpire(0)
            ->setSecure(true) //only via ssl
            ->setHttpOnly(true) //never by browser
            ->setPath('/')
            ->create();

        return true;
    }
}
