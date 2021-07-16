<?php
namespace Minds\Core\OAuth;

use Minds\Core\Di\Di;
use Minds\Common\Cookie;
use Zend\Diactoros\ServerRequestFactory;
use League\OAuth2\Server\CryptTrait;

/**
 * Why does this helper exists?
 * The OAuth-Server library does not support extending the claims very well
 * As we can't add or access the auth code payload, we need to hack around it.
 * The nonce is used by clients only, not by the server.
 * Minds can read and set the nonce cookie but other services can not.
 */
class NonceHelper
{
    use CryptTrait;

    /** @var string */
    const CACHE_PREFIX = 'oauth_nonce::';

    /** @var PsrWrapper */
    protected $cache;

    public function __construct($cache = null)
    {
        $this->cache = $cache ?? Di::_()->get('Cache');
        $this->encryptionKey = (Di::_()->get('Config')->get('oauth') ?? [])['encryption_key'] ?? null;
    }

    /**
     * @param string $userGuid
     * @param string $nonce
     */
    public function setNonce(string $userGuid, string $nonce)
    {
        $this->cache->set(self::CACHE_PREFIX . $userGuid, $nonce);
    }

    /**
     * Will return a nonce from global request vars
     * @return string
     */
    public function getNonce(): ?string
    {
        $serverRequest = ServerRequestFactory::fromGlobals();

        $body = $serverRequest->getParsedBody();

        if ($body) {
            $code = $body['code'] ?? null;
            
            if (!$code) {
                return null;
            }

            $payload = json_decode($this->decrypt($code), true);

            $nonce = $this->cache->get(self::CACHE_PREFIX . $payload['user_id']);

            if ($nonce) {
                return $nonce;
            }
        }
        return null;
    }
}
