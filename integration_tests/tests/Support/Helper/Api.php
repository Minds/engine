<?php

namespace Tests\Support\Helper;

use Codeception\Exception\ModuleException;
use Codeception\Module;
use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Symfony\Component\BrowserKit\Cookie;

class Api extends Module
{
    /**
     * @throws ModuleException
     */
    public function setCookie(string $name, mixed $value)
    {
        $cookie = Cookie::fromString("$name=$value;", $this->getModule('REST')->_getConfig("url"));
        $this->getModule("REST")->client->getCookieJar()->set($cookie);
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
}
