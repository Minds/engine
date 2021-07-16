<?php
/**
 * Jwt
 * @author edgebal
 */

namespace Minds\Common;

use DateTimeImmutable;
use Exception;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Claim;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Validation\Constraint\SignedWith;

class Jwt
{
    /** @var string */
    protected $key;

    /** @var Configuration */
    protected $jwtConfig;

    /**
     * @param string $key
     * @return Jwt
     */
    public function setKey(string $key): Jwt
    {
        $this->key = $key;

        $this->jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($key));

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

        $builder = $this->jwtConfig->builder();

        foreach ($payload as $key => $value) {
            $builder->withClaim($key, $value);
        }

        if ($exp !== null) {
            $builder->expiresAt((new DateTimeImmutable())->setTimestamp($exp));
        }

        if ($nbf !== null) {
            $builder->canOnlyBeUsedAfter((new DateTimeImmutable())->setTimestamp($nbf));
        }

        return (string) $builder->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey())->toString();
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

        $token = $this->jwtConfig->parser()->parse($jwt);

        if (!$this->jwtConfig->validator()->validate($token, new SignedWith($this->jwtConfig->signer(), $this->jwtConfig->signingKey()))) {
            throw new Exception('Invalid JWT');
        }

        return $token->claims()->all();
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
