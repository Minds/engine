<?php
/**
 * XSRF Protections
 */
namespace Minds\Core\Security;

use Minds\Common\Cookie;
use Minds\Core\Session;
use Minds\Core\Sessions\Manager as SessionsManager;
use Psr\Http\Message\ServerRequestInterface;

class XSRF
{
    public static function buildToken() : string
    {
        $bytes = openssl_random_pseudo_bytes(128);
        return hash('sha512', $bytes);
    }

    public static function validateRequest(ServerRequestInterface $request = null) : bool
    {
        if (Session::isLoggedin()) {
            if ($request == null)
                return false;

            $sessionsManager = (new SessionsManager())->withRouterRequest($request);
            return $sessionsManager->validateSession();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            return true; // XSRF only needed for modifiers
        }

        if (!isset($_SERVER['HTTP_X_XSRF_TOKEN'])) {
            return false;
        }

        if ($_SERVER['HTTP_X_XSRF_TOKEN'] == $_COOKIE['XSRF-TOKEN']) {
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
        if (
            !$force
            && (
                Session::isLoggedin()
                || isset($_COOKIE['XSRF-TOKEN'])
            )
        ) {
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
            ->create();
    }
}
