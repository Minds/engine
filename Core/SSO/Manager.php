<?php
/**
 * Manager
 * @author edgebal
 */

namespace Minds\Core\SSO;

use Exception;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Data\cache\abstractCacher;
use Minds\Core\Di\Di;
use Minds\Core\Sessions\Manager as SessionsManager;

class Manager
{
    /** @var int */
    const JWT_EXPIRE = 300;

    /** @var Config */
    protected $config;

    /** @var abstractCacher */
    protected $cache;

    /** @var Jwt */
    protected $jwt;

    /** @var SessionsManager */
    protected $sessions;

    /** @var Delegates\ProDelegate */
    protected $proDelegate;

    /** @var string */
    protected $domain;

    /**
     * Manager constructor.
     * @param Config $config
     * @param abstractCacher $cache
     * @param Jwt $jwt
     * @param SessionsManager $sessions
     * @param Delegates\ProDelegate $proDelegate
     */
    public function __construct(
        $config = null,
        $cache = null,
        $jwt = null,
        $sessions = null,
        $proDelegate = null
    ) {
        $this->config = $config ?: Di::_()->get('Config');
        $this->cache = $cache ?: Di::_()->get('Cache');
        $this->jwt = $jwt ?: new Jwt();
        $this->sessions = $sessions ?: Di::_()->get('Sessions\Manager');
        $this->proDelegate = $proDelegate ?: new Delegates\ProDelegate();
    }

    /**
     * @param string $domain
     * @return Manager
     */
    public function setDomain(string $domain): Manager
    {
        $this->domain = $domain;
        return $this;
    }

    /**
     * @return bool
     */
    protected function isAllowed(): bool
    {
        if ($this->proDelegate->isAllowed($this->domain)) {
            return true;
        }

        return false;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function generateToken(): ?string
    {
        if (!$this->isAllowed()) {
            throw new Exception('Invalid domain');
        }

        $now = time();
        $session = $this->sessions->getSession();

        if (!$session || !$session->getUserGuid()) {
            return null;
        }

        $key = $this->config->get('oauth')['encryption_key'] ?? '';

        if (!$key) {
            throw new Exception('Invalid encryption key');
        }

        $sessionToken = (string) $session->getToken()->toString();
        $sessionTokenHash = hash('sha256', $key . $sessionToken);

        $ssoKey = implode(':', ['sso', $this->domain, $sessionTokenHash, $this->jwt->randomString()]);

        $jwt = $this->jwt
            ->setKey($key)
            ->encode([
                'key' => $ssoKey,
                'domain' => $this->domain,
            ], $now, $now + static::JWT_EXPIRE);

        $this->cache
            ->set($ssoKey, $sessionToken, static::JWT_EXPIRE * 2);

        return $jwt;
    }

    /**
     * @param string $jwt
     * @return void
     * @throws Exception
     */
    public function authorize(string $jwt): void
    {
        if (!$jwt) {
            throw new Exception('Invalid JTW');
        }

        if (!$this->isAllowed()) {
            throw new Exception('Invalid domain');
        }

        $key = $this->config->get('oauth')['encryption_key'] ?? '';

        if (!$key) {
            throw new Exception('Invalid encryption key');
        }

        $data = $this->jwt
            ->setKey($key)
            ->decode($jwt);

        if ($this->domain !== $data['domain']) {
            throw new Exception('Domain mismatch');
        }

        $ssoKey = $data['key'];

        $sessionToken = $this->cache
            ->get($ssoKey);

        if ($sessionToken) {
            $this->sessions
                ->withString($sessionToken)
                ->save();

            $this->cache
                ->destroy($ssoKey);
        }
    }
}
