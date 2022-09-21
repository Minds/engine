<?php

namespace Minds\Core\Twitter\Client;

use Minds\Core\Twitter\Client\DTOs\TweetDTO;

/**
 *
 */
interface TwitterClientInterface
{
    public function requestOAuthAuthorizationCodeUrlDetails(): array;

    public function generateOAuthAccessToken(string $authorizationCode): array;

    public function refreshOAuthAccessToken(string $refreshToken): array;

    public function postTweet(TweetDTO $tweet, string $accessToken): bool;
}
