<?php

namespace Minds\Core\Feeds\Seen;

use Minds\Common\Cookie;

class SeenCacheKeyCookie
{
    private const COOKIE_NAME = "seen-entities";
    private string $cookieValue;

    public function __construct()
    {
        $this->cookieValue = $_COOKIE[self::COOKIE_NAME] ?? "";
    }

    public function getValue(): string
    {
        return $this->cookieValue;
    }

    public function createCookie(): self
    {
        $this->generateRandomCookieValue();

        $cookie = new Cookie();

        $cookie
            ->setName(self::COOKIE_NAME)
            ->setValue($this->cookieValue)
            ->setPath("/")
            ->setHttpOnly(true)
            ->setSecure(true)
            ->create();

        return $this;
    }

    private function generateRandomCookieValue(): string
    {
        if (!empty($this->cookieValue)) {
            return $this->cookieValue;
        }

        $bytes = openssl_random_pseudo_bytes(128);
        return $this->cookieValue = hash('sha512', $bytes);
    }
}
