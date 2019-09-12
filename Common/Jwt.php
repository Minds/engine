<?php
/**
 * Jwt
 * @author edgebal
 */

namespace Minds\Common;

use Exception;
use Firebase\JWT\JWT as FirebaseJWT;

class Jwt
{
    /** @var string */
    protected $key;

    /**
     * @param string $key
     * @return Jwt
     */
    public function setKey(string $key)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param object|array $payload
     * @return string
     * @throws Exception
     */
    public function encode($payload)
    {
        if (!$this->key) {
            throw new Exception('Invalid JWT key');
        }

        return FirebaseJWT::encode($payload, $this->key, 'HS256');
    }

    /**
     * @param string $jwt
     * @return object
     * @throws Exception
     */
    public function decode($jwt)
    {
        if (!$this->key) {
            throw new Exception('Invalid JWT key');
        }

        return FirebaseJWT::decode($jwt, $this->key, ['HS256']);
    }

    /**
     * @return string
     */
    public function randomString()
    {
        $bytes = openssl_random_pseudo_bytes(128);
        return hash('sha512', $bytes);
    }
}
