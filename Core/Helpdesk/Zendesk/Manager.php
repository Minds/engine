<?php
namespace Minds\Core\Helpdesk\Zendesk;

use DateTimeImmutable;
use Exception;
use Minds\Core\Di\Di;
use Lcobucci\JWT\Configuration as JwtConfiguration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

/**
 * Zendesk Manager
 * @package Minds\Core\Security\TOTP
 */
class Manager
{
    /** @var Lcobucci\JWT\Configuration */
    private $jwtConfig;

    /** @var Config */
    private $config;

    public function __construct(
        $config = null,
    ) {
        $this->config = $config ?? Di::_()->get('Config');

        $this->jwtConfig = JwtConfiguration::forSymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->config->get('zendesk')['private_key'])
        );
    }

    /**
     * Generates JWT token.
     * @param $user - user from request
     * @return string base64 encoded jwt.
     */
    public function getJwt($user): string
    {
        if (!$user) {
            throw new Exception("User must be provided");
        }

        $token = $this->jwtConfig->builder()
            ->identifiedBy(md5(time() . rand())) //jti
            ->issuedAt(new DateTimeImmutable()) // iat
            ->withClaim('name', $user->getUsername())
            ->withClaim('email', $user->getEmail())
            ->withClaim('external_id', 'minds-guid:'.$user->getGuid())
            ->withClaim('role', 'user') // fixed to default
            ->getToken($this->jwtConfig->signer(), $this->jwtConfig->signingKey());

        return $token->toString();
    }
}
