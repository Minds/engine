<?php
/**
 * Jwt
 * @author edgebal
 */

namespace Minds\Common;

use Exception;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;

class Jwt
{
    /** @var string */
    protected $key;

    /**
     * @param string $key
     * @return Jwt
     */
    public function setKey(string $key): Jwt
    {
        $this->key = $key;
        return $this;
    }

    /**
     * @param object|array $payload
     * @param int|null $exp
     * @param int|null $nbf
     * @return string
     * @throws Exception
     */
    public function encode($payload, $exp = null, $nbf = null): string
    {
        if (!$this->key) {
            throw new Exception('Invalid JWT key');
        }

        $builder = new Builder();

        foreach ($payload as $key => $value) {
            $builder->set($key, $value);
        }

        if ($exp !== null) {
            $builder->setExpiration($exp);
        }

        if ($nbf !== null) {
            $builder->setNotBefore($nbf);
        }

        $builder->sign(new Sha256(), $this->key);

        return (string) $builder->getToken();
    }

    /**
     * @param string $jwt
     * @return array
     * @throws Exception
     */
    public function decode($jwt): array
    {
        if (!$this->key) {
            throw new Exception('Invalid JWT key');
        }

        $token = (new Parser())->parse($jwt);

        if (!$token->verify(new Sha256(), $this->key)) {
            throw new Exception('Invalid JWT');
        }

        return array_map(function (Claim $claim) {
            return $claim->getValue();
        }, $token->getClaims());
    }

    /**
     * @return string
     */
    public function randomString(): string
    {
        $bytes = openssl_random_pseudo_bytes(128);
        return hash('sha512', $bytes);
    }
}
