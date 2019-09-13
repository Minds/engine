<?php
/**
 * Security
 * @author edgebal
 */

namespace Minds\Core\Pro\Domain;

use Exception;
use Minds\Common\Cookie;
use Minds\Common\Jwt;
use Minds\Core\Config;
use Minds\Core\Di\Di;
use Zend\Diactoros\ServerRequest;

class Security
{
    /** @var string */
    const JWT_COOKIE_NAME = 'PRO-XSRF-JWT';

    /** @var string */
    const XSRF_COOKIE_NAME = 'XSRF-TOKEN';

    /** @var Cookie */
    protected $cookie;

    /** @var Jwt */
    protected $jwt;

    /** @var Config */
    protected $config;

    /**
     * Security constructor.
     * @param Cookie $cookie
     * @param Jwt $jwt
     * @param Config $config
     */
    public function __construct(
        $cookie = null,
        $jwt = null,
        $config = null
    ) {
        $this->cookie = $cookie ?: new Cookie();
        $this->jwt = $jwt ?: new Jwt();
        $this->config = $config ?: Di::_()->get('Config');
    }

    /**
     * @param string $domain
     * @return string
     * @throws Exception
     */
    public function setUp($domain)
    {
        $nonce = $this->jwt->randomString();
        $now = time();
        $exp = $now + 60;

        $jwt = $this->jwt
            ->setKey($this->getEncryptionKey())
            ->encode([
                'iss' => 'minds.com',
                'aud' => strtolower($domain),
                'nbf' => $now,
                'exp' => $exp,
                'nonce' => $nonce,
            ]);

        $this->cookie
            ->setName(static::JWT_COOKIE_NAME)
            ->setValue($jwt)
            ->setExpire($exp)
            ->setPath('/')
            ->setHttpOnly(false)
            ->create();

        $this->cookie
            ->setName(static::XSRF_COOKIE_NAME)
            ->setValue($nonce)
            ->setExpire(0)
            ->setPath('/')
            ->setHttpOnly(false)
            ->create();

        return $jwt;
    }

    /**
     * @param ServerRequest $request
     */
    public function syncCookies(ServerRequest $request)
    {
        $jwt = $request->getServerParams()['HTTP_X_PRO_XSRF_JWT'] ?? '';

        if (!$jwt) {
            return;
        }

        try {
            $data = $this->jwt
                ->setKey($this->getEncryptionKey())
                ->decode($jwt);

            if (($_COOKIE[static::XSRF_COOKIE_NAME] ?? null) === $data->nonce) {
                return;
            }

            $this->cookie
                ->setName(static::XSRF_COOKIE_NAME)
                ->setValue($data->nonce)
                ->setExpire(0)
                ->setPath('/')
                ->setHttpOnly(false)
                ->create();
        } catch (Exception $e) {
        }
    }

    /**
     * @return string
     */
    protected function getEncryptionKey()
    {
        return $this->config->get('oauth')['encryption_key'] ?? '';
    }
}
