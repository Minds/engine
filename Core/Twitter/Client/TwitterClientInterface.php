<?php

namespace Minds\Core\Twitter\Client;

/**
 *
 */
interface TwitterClientInterface
{
    public function requestOAuthAuthorizationCodeUrlDetails(): array;

    public function generateOAuthAccessToken(string $authorizationCode): array;

    public function refreshOAuthAccessToken(string $refreshToken): array;
}
