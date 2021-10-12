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
    public function __construct(
        private ServerRequestInterface $request,
        private SessionsManager $sessionsManager
    ) {
    }

    private function buildToken() : string
    {
        $sessionId = "";
        if (Session::isLoggedin()) {
            $sessionId = $this->getSessionId();
        }
        return $this->createTokenString($sessionId);
    }

    private function createRandomHash() : string
    {
        $bytes = openssl_random_pseudo_bytes(128);
        return hash('sha512', $bytes);
    }

    private function createTokenString(string $sessionId) : string
    {
        $token = $this->createRandomHash();

        if ($sessionId != "") {
            $token .= "-${sessionId}";
        }
        return $token;
    }

    private function getSessionId() : string
    {
        $this->sessionsManager->setUser(Session::getLoggedinUser());

        return $this->sessionsManager
            ->withRouterRequest($this->request)
            ->getSession()
            ->getId();
    }

    /**
     * @param ServerRequestInterface $request
     * @return $this
     */
    public function setRequest(ServerRequestInterface $request) : self
    {
        $this->request = $request;
        return $this;
    }

    /**
     * @param SessionsManager $sessionsManager
     * @return $this
     */
    public function setSessionsManager(SessionsManager $sessionsManager) : self
    {
        $this->sessionsManager = $sessionsManager;
        return $this;
    }

    public function validateRequest() : bool
    {
        if ($this->request->getMethod() == "GET") {
            return true;
        }

        if (!isset($this->request->getServerParams()['HTTP_X_XSRF_TOKEN'])) {
            return false;
        }

        $xsrfToken = $this->request->getCookieParams()['XSRF-TOKEN'];
        if ($this->request->getServerParams()['HTTP_X_XSRF_TOKEN'] == $xsrfToken) {
            if (!Session::isLoggedin()) {
                return true;
            }

            $parts = $this->parseToken($xsrfToken);
            if (
                Session::isLoggedin()
                && !empty($parts[1])
                && $parts[1] == $this->getSessionId()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set the cookie
     * @return bool
     */
    public function setCookie($force = false) : bool
    {
        if (
            !$force
            && array_key_exists('XSRF-TOKEN', $this->request->getCookieParams())
        ) {
            $tokenParts = $this->parseToken($this->request->getCookieParams()['XSRF-TOKEN']);

            // Should we check the actual sessionId with the sessionId found in the Token
            // to make sure that the token sent is not the one of another user?
            if (Session::isLoggedin() && !empty($tokenParts[1])) {
                return false;
            }

            if (!Session::isLoggedin() && empty($tokenParts[1])) {
                return false;
            }
        }
        $token = self::buildToken();

        $cookie = $this->prepareCookie($token);
        $cookie->create();
        return true;
    }

    private function parseToken(string $token) : array
    {
        return preg_split("/-/", $token);
    }


    private function prepareCookie(string $token) : Cookie
    {
        return (new Cookie())
            ->setName('XSRF-TOKEN')
            ->setValue($token)
            ->setExpire(0)
            ->setPath('/')
            ->setHttpOnly(false);
    }
}
