<?php
namespace Minds\Core\OAuth;

use Minds\Common\Cookie;
use Zend\Diactoros\ServerRequestFactory;

/**
 * Why does this helper exists?
 * The OAuth-Server library does not support extending the claims very well
 * As we can't add or access the auth code payload, we need to hack around it.
 * The nonce is used by clients only, not by the server.
 * Minds can read and set the nonce cookie but other services can not.
 */
class NonceHelper
{
    /** @var string */
    const COOKIE_NAME = 'oauth_nonce';

    /**
     * Sets the nonce cookie
     * @param string $nonce
     * @return void
     */
    public static function setNonce(string $nonce)
    {
        // Upon there being a nonce, set this in a temporary cookie
        $cookie = new Cookie();
        $cookie->setName(self::COOKIE_NAME);
        $cookie->setValue($nonce);
        $cookie->setExpire(time() + 300); // Expire in 5 mins
        $cookie->create();
    }

    /**
     * Will return a nonce from global request vars
     * @return string
     */
    public static function getNonce(): ?string
    {
        $serverRequest = ServerRequestFactory::fromGlobals();
        if ($nonce = $serverRequest->getCookieParams()['oauth_nonce']) {
            return $nonce;
        }
        return null;
    }
}
