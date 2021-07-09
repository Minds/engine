<?php
/**
 * Minds JWT Session Manager
 */

namespace Minds\Core\Sessions;

use Exception;
use Minds\Common\Cookie;
use Minds\Common\IpAddress;
use Minds\Core;
use Minds\Core\Di\Di;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer\Rsa\Sha512;
use Minds\Common\Repository\Response;
use Minds\Entities\User;

class Manager
{
    /** @var Repository $repository */
    private $repository;

    /** @var Core\Config $config */
    private $config;

    /** @var Cookie $cookie */
    private $cookie;

    /** @var IpAddress */
    protected $ipAddress;

    /** @var Delegates\SentryScopeDelegate $sentryScopeDelegate */
    private $sentryScopeDelegate;

    /** @var Delegates\UserLanguageDelegate */
    private $userLanguageDelegate;

    /** @var Session $session */
    private $session;

    /** @var User $user */
    private $user;

    /** @var JWT\Builder */
    private $jwtBuilder;

    public function __construct(
        $repository = null,
        $config = null,
        $cookie = null,
        $jwtBuilder = null,
        $jwtParser = null,
        $ipAddress = null,
        $sentryScopeDelegate = null,
        $userLanguageDelegate = null
    ) {
        $this->repository = $repository ?: new Repository;
        $this->config = $config ?: Di::_()->get('Config');
        $this->cookie = $cookie ?: new Cookie;
        $this->jwtBuilder = $jwtBuilder ?: new JWT\Builder;
        $this->jwtParser = $jwtParser ?: new JWT\Parser;
        $this->ipAddress = $ipAddress ?? new IpAddress();
        $this->sentryScopeDelegate = $sentryScopeDelegate ?: new Delegates\SentryScopeDelegate;
        $this->userLanguageDelegate = $userLanguageDelegate ?: new Delegates\UserLanguageDelegate();
    }

    /**
     * Set the session
     * @param Session $session
     * @return $this
     */
    public function setSession($session)
    {
        $this->session = $session;
        return $this;
    }

    /**
     * Return the current session
     * @return Session
     */
    public function getSession()
    {
        return $this->session;
    }

    /**
     * Set the user for the session
     * @param User $user
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;
        return $user;
    }

    /**
     * Build session from jwt cookie
     * @param $request
     * @return Manager
     */
    public function withRouterRequest($request): Manager
    {
        $cookies = $request->getCookieParams();
        if (!isset($cookies['minds_sess'])) {
            return $this;
        }

        return $this->withString((string) $cookies['minds_sess']);
    }

    /**
     * @param string $sessionToken
     * @return Manager
     */
    public function withString(string $sessionToken): Manager
    {
        try {
            $token = $this->jwtParser->parse($sessionToken);
            $token->getHeaders();
            $token->getClaims();
        } catch (\Exception $e) {
            return $this;
        }

        $key = $this->config->get('sessions')['public_key'];

        if (!$token->verify(new Sha512, new LocalFileReference($key))) {
            return $this;
        }

        $id = $token->getHeader('jti');
        $user_guid = $token->getClaim('user_guid');
        $expires = $token->getClaim('exp');

        $session = new Session;
        $session
            ->setId($id)
            ->setUserGuid($user_guid)
            ->setToken($token)
            ->setExpires($expires);

        if (!$this->validateSession($session)) {
            return $this;
        }

        $this->session = $session;

        // Sets the global user
        Core\Session::setUserByGuid($user_guid);

        // Generate JWT cookie for sockets
        // Hack, needs refactoring
        Core\Session::generateJWTCookie($session);

        // Allow Sentry to attach user metadata
        $this->sentryScopeDelegate->onSession($session);

        return $this;
    }

    /**
     * Validate the session
     * @param Session $session
     * @return bool
     */
    public function validateSession($session)
    {
        $validated = $this->repository->get(
            $session->getUserGuid(),
            $session->getId()
        );

        if (!$validated) {
            return false;
        }

        if (
            !$session->getId()
            || $session->getId() != $validated->getId()
        ) {
            return false;
        }

        if (
            !$session->getUserGuid()
            || $session->getUserGuid() != $validated->getUserGuid()
        ) {
            return false;
        }

        if (
            !$session->getExpires()
            || $session->getExpires() != $validated->getExpires()
            || $session->getExpires() < time()
        ) {
            return false;
        }

        // Update the last active and timestamp, if validated past 15 mins
        if ($validated->getLastActive() < time() - 1500) {
            $session->setLastActive(time());
            $session->setIp($this->ipAddress->get());
            $this->repository->update($session, [ 'last_active', 'ip' ]);
        }

        return true;
    }

    /**
     * Create the session
     * @return $this
     */
    public function createSession()
    {
        $id = $this->generateId();
        $expires = time() + (60 * 60 * 24 * 30); // 30 days

        $token = $this->jwtBuilder
            //->issuedBy($this->config->get('site_url'))
            //->canOnlyBeUsedBy($this->config->get('site_url'))
            ->setId($id)
            ->setHeader('jti', $id)
            ->setExpiration($expires)
            ->set('user_guid', (string) $this->user->getGuid())
            ->getToken(new Sha512, new LocalFileReference($this->config->get('sessions')['private_key']));

        $this->session = new Session();
        $this->session
            ->setId($id)
            ->setToken($token)
            ->setUserGuid($this->user->getGuid())
            ->setExpires($expires)
            ->setLastActive(time())
            ->setIp($this->ipAddress->get());

        $this->userLanguageDelegate->setCookie($this->user);

        return $this;
    }

    private function generateId()
    {
        $bytes = openssl_random_pseudo_bytes(128);
        return hash('sha512', $bytes);
    }

    /**
     * Save the session to the database and client
     * @return $this
     */
    public function save()
    {
        $this->repository->add($this->session);

        $this->cookie
            ->setName('minds_sess')
            ->setValue($this->session->getToken())
            ->setExpire($this->session->getExpires())
            ->setSecure(true) //only via ssl
            ->setHttpOnly(true) //never by browser
            ->setPath('/')
            ->create();

        return $this;
    }

    /**
     * Delete all jwt sessions for a given user
     * @param User $user
     * @return bool
     */
    public function deleteAll(User $user = null)
    {
        if (!$user) {
            $user = Core\Session::getLoggedInUser();
        }

        $response = $this->repository->deleteAll($user);

        if ($user->getGuid() === Core\Session::getLoggedInUserGuid()) {
            $this->removeFromClient();
        }

        return $response;
    }

    /**
     * Remove the session from the database
     * If deleting current session, remove from client too
     * @param Sessions\Session $session
     * @return bool
     */
    public function delete($session = null)
    {
        $sessionToDelete = $session ?: $this->session;

        if (!$sessionToDelete) {
            throw new Exception("Session required");
        }

        $response = $this->repository->delete($sessionToDelete);

        if (!$response) {
            throw new Exception("Could not delete session");
        }

        if (!$session || $session === $this->session) {
            $this->removeFromClient();
        }

        return $response;
    }

    /**
     * Remove current session from client
     * @return void
     */
    public function removeFromClient()
    {
        $this->cookie
        ->setName('minds_sess')
        ->setValue('')
        ->setExpire(time() - 3600)
        ->setSecure(true) //only via ssl
        ->setHttpOnly(true) //never by browser
        ->setPath('/')
        ->create();
    }

    /**
     * Return all jwt sessions
     * @param User $user
     * @return array
     */
    public function getList(User $user): array
    {
        $response = $this->repository->getList($user->getGuid());

        return $response;
    }

    /**
     * Return the count of active sessions
     * @return int
     */
    public function getActiveCount()
    {
        return $this->repository->getCount($this->user->getGuid());
    }
}
