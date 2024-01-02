<?php

declare(strict_types=1);

namespace Tests\Support\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

use Codeception\Exception\ModuleException;
use Codeception\Module;

/**
 *
 */
class Authentication extends Module
{
    private const LOGIN_URI = 'v1/authenticate';

    /**
     * @param string $username
     * @param string $password
     * @return void
     * @throws ModuleException
     */
    public function loginWithDetails(string $username, string $password): void
    {
        /**
         * @var Module\REST $client
         */
        $client = $this->getModule("REST");

        /**
         * @var Api $apiHelper
         */
        $apiHelper = $this->getModule(Api::class);

        $apiHelper->setCookie("XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $client->haveHttpHeader("X-XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $apiHelper->setRateLimitBypass();
        $apiHelper->setStagingCookie();

        $client->send("POST", self::LOGIN_URI, [
            'username' => $username,
            'password' => $password
        ]);
    }

    public function login(): void
    {
        /**
         * @var Module\REST $client
         */
        $client = $this->getModule("REST");

        /**
         * @var Api $apiHelper
         */
        $apiHelper = $this->getModule(Api::class);

        $apiHelper->setCookie("XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $client->haveHttpHeader("X-XSRF-TOKEN", "13b900e725e3fe5ea60464d3c8bf7423e2d215ed5c473ccca34118cb0e7c538432b89cecaa98c424246ca789ec464a0516166d492c82f3573b3d2446903f31e1");
        $apiHelper->setRateLimitBypass();
        $apiHelper->setStagingCookie();

        $client->send("POST", self::LOGIN_URI, [
            'username' => $_ENV['SUPERMIND_REQUESTER_USERNAME'],
            'password' => $_ENV['SUPERMIND_REQUESTER_PASSWORD']
        ]);
    }
}
