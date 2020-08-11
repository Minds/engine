<?php
namespace Minds\Core\Security;

use Minds\Core\Config;
use Minds\Core\Di\Di;
use Minds\Core\Session;
use Lcobucci\JWT;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Zend\Diactoros\Uri;

class SignedUri
{
    /** @Var JWT\Builder $jwtBuilder */
    private $jwtBuilder;

    /** @var JWT\Parser $jwtParser */
    private $jwtParser;

    /** @var Config $config */
    private $config;

    public function __construct($jwtBuilder = null, $jwtParser = null, $config = null)
    {
        $this->jwtBuilder = $jwtBuilder ?? new JWT\Builder;
        $this->jwtParser = $jwtParser ?? new JWT\Parser();
        $this->config = $config ?? Di::_()->get('Config');
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

        $expires = (new \DateTime())->modify('midnight first day of next month')->modify('+1 month')->getTimestamp();

        $token = (new $this->jwtBuilder)
            //->setId((string) $uri)
            ->setExpiration($expires)
            ->set('uri', (string) $uri)
            ->set('user_guid', Session::isLoggedIn() ? (string) Session::getLoggedInUser()->getGuid() : null)
            ->sign(new Sha256, $this->config->get('sessions')['private_key'])
            ->getToken();
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
            $token = $this->jwtParser->parse($providedSig);
        } catch (\Exception $e) {
            return false;
        }

        if (!$token->verify(new Sha256, $this->config->get('sessions')['private_key'])) {
            return false;
        }
        return ((string) $token->getClaim('uri') === (string) $providedUri->withQuery(''));
    }
}
