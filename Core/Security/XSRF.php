<?php
/**
 * XSRF Protections
 */
namespace Minds\Core\Security;

use Minds\Core;
use Minds\Common\Cookie;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ServerRequestInterface;

class XSRF
{
    public static function buildToken()
    {
        $bytes = openssl_random_pseudo_bytes(128);
        return hash('sha512', $bytes);
    }

    public static function validateRequest(ServerRequestInterface $request)
    {
        if ($request->getMethod() === 'GET') {
            return true; // XSRF only needed for modifiers
        }

        $xsrfHeaderVal = $request->hasHeader('X-XSRF-TOKEN') ? $request->getHeader('X-XSRF-TOKEN')[0] : null;

        if (!$xsrfHeaderVal) {
            return false;
        }

        if ($xsrfHeaderVal == $request->getCookieParams()['XSRF-TOKEN']) {
            return true;
        }

        return false;
    }

    /**
     * Set the cookie
     * @return void
     */
    public static function setCookie($force = false)
    {
        if (!$force && isset($_COOKIE['XSRF-TOKEN'])) {
            return;
        }
        $token = self::buildToken();

        $cookie = new Cookie();
        $cookie
            ->setName('XSRF-TOKEN')
            ->setValue($token)
            ->setExpire(0)
            ->setPath('/')
            ->setHttpOnly(false) //must be able to read in JS
            ->setSameSite('None')
            ->create();
    }
}
