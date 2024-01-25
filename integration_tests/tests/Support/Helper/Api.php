<?php

namespace Tests\Support\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use Codeception\Module\REST;
use DateTimeImmutable;
use Google\Service\AndroidPublisher\VoidedPurchase;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\BrowserKit\Cookie;

class Api extends Module
{
    /**
     * @return REST
     * @throws ModuleException
     */
    private function _getApiClient(): REST
    {
        /**
         * @type REST
         */
        return $this->getModule("REST");
    }
    
    /**
     * @throws ModuleException
     */
    public function clearCookies(): void
    {
        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");
        $apiClient->client->getCookieJar()->clear();
    }

    /**
     * @throws ModuleException
     */
    public function getCookie(string $name): ?Cookie
    {
        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");
        return $apiClient->client->getCookieJar()->get($name);
    }

    /**
     * @throws ModuleException
     */
    public function setCookie(string $name, mixed $value)
    {
        $cookie = Cookie::fromString("$name=$value;", $this->getModule('REST')->_getConfig("url"));
        /**
         * @var REST $apiClient
         */
        $apiClient = $this->getModule("REST");
        $apiClient->client->getCookieJar()->set($cookie);
    }

    /**
     * Check a cookie has a given value.
     * @param string $cookieKey - key/name of cookie.
     * @param string $value - value to assert the cookie has.
     * @return void
     * @throws ModuleException
     */
    public function checkCookieValue(string $cookieKey, string $value): void
    {
        $cookie = $this->getCookie($cookieKey);
        $this->assertTrue($cookie->getValue() === $value);
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function setCaptchaBypass(): void
    {
        $signing_secret = $_ENV['BYPASS_SIGNING_KEY'] ?? "testing";

        $jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($signing_secret));

        $token = $jwtConfig
            ->builder()
            ->expiresAt(new DateTimeImmutable("+5m"))
            ->withClaim('data', "captcha_bypass")
            ->getToken(
                $jwtConfig->signer(),
                $jwtConfig->signingKey()
            );

        $this->setCookie('captcha_bypass', $token->toString());
    }

    /**
     * @return void
     * @throws ModuleException
     */
    public function setRateLimitBypass(): void
    {
        $signing_secret = $_ENV['BYPASS_SIGNING_KEY'] ?? "testing";

        $jwtConfig = Configuration::forSymmetricSigner(new Sha256(), InMemory::plainText($signing_secret));

        $token = $jwtConfig
            ->builder()
            ->expiresAt(new DateTimeImmutable("+5m"))
            ->withClaim('timestamp_ms', time() * 1000)
            ->getToken(
                $jwtConfig->signer(),
                $jwtConfig->signingKey()
            );

        $this->setCookie('rate_limit_bypass', $token->toString());
    }

    /**
     * @param array{key: mixed, value: string} $params
     * @return string
     */
    public function generateUrlQueryParams(array $params): string
    {
        return implode(
            "&",
            array_map(
                function (array $param): string {
                    return $param['key'] . "=" . $param['value'];
                },
                $params
            )
        );
    }
    
    /**
     * @return void
     * @throws ModuleException
     */
    public function setStagingCookie(): void
    {
        $this->setCookie('staging', 1);
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $params
     * @return void
     * @throws ModuleException
     */
    public function callApiEndpoint(string $method, string $uri, array $params)
    {
        $apiClient = $this->_getApiClient();
    }
}
