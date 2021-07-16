<?php
namespace Minds\Core\Security;

use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Zend\Diactoros\Uri;

class SignedUri
{
    /** @var JWT\Configuration */
    protected $jwtConfig;

    /** @var Config */
    protected $config;

    public function __construct(
        ?JWT\Configuration $jwtConfig = null,
        ?Config $config = null
    ) {
        $this->config = $config ?? Di::_()->get('Config');
        $this->jwtConfig = $jwtConfig;
    }

    /**
     * @return JWT\Configuration
     */
    protected function getJwtConfig(): JWT\Configuration
    {
        if (!$this->jwtConfig) {
            $this->jwtConfig = JWT\Configuration::forAsymmetricSigner(new Sha256(), LocalFileReference::file($this->config->get('sessions')['private_key']), LocalFileReference::file($this->config->get('sessions')['public_key']));
        }
        return $this->jwtConfig;
    }

    /**
     * Sign the uri
     * @param string $uri
     * @param int $ttl - defaults to 1 day
     * @return string
     */
    public function sign($uri, $expires = 86400): string
    {
        $uri = new Uri($uri);

        $expires = (new \DateTimeImmutable())->modify('midnight first day of next month')->modify('+1 month');

        $token = $this->getJwtConfig()->builder()
            //->setId((string) $uri)
            ->expiresAt($expires)
            ->withClaim('uri', (string) $uri)
            ->withClaim('user_guid', Session::isLoggedIn() ? (string) Session::getLoggedInUser()->getGuid() : null)
            ->getToken($this->getJwtConfig()->signer(), $this->getJwtConfig()->signingKey())
            ->toString();
        $signedUri = $uri->withQuery("jwtsig=$token");
        return (string) $signedUri;
    }

    /**
     * Confirm signed uri
     * @param string $uri
     * @return string
     */
    public function confirm($uri): bool
    {
        $providedUri = new Uri($uri);
        parse_str($providedUri->getQuery(), $queryParams);
        $providedSig = $queryParams['jwtsig'];

        try {
            $token = $this->getJwtConfig()->parser()->parse($providedSig);
        } catch (\Exception $e) {
            return false;
        }

        if (!$this->getJwtConfig()->validator()->validate($token, new SignedWith($this->getJwtConfig()->signer(), $this->getJwtConfig()->signingKey()))) {
            return false;
        }
        return ((string) $token->claims()->get('uri') === (string) $providedUri->withQuery(''));
    }
}
