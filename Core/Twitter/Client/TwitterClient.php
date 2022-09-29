<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\RequestOptions;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;
use Minds\Core\Twitter\Client\DTOs\TweetDTO;

/**
 *
 */
class TwitterClient implements TwitterClientInterface
{
    private const API_BASE_URI = 'https://api.twitter.com';

    private const OAUTH_TOKEN_REQUEST_CALLBACK = 'api/v3/twitter/oauth';

    /**
     * @param MindsConfig|null $mindsConfig
     * @param HttpClient|null $httpClient
     */
    public function __construct(
        private ?MindsConfig $mindsConfig = null,
        private ?HttpClient $httpClient = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');
        $this->httpClient ??= new HttpClient([
            'base_uri' => self::API_BASE_URI
        ]);
    }

    private function getBearerToken(): string
    {
        return base64_encode($this->mindsConfig->get('twitter')['client_id'] . ":" . $this->mindsConfig->get('twitter')['client_secret']);
    }

    public function requestOAuthAuthorizationCodeUrlDetails(): array
    {
        return [
            'response_type' => 'code',
            'client_id' => $this->mindsConfig->get('twitter')['client_id'],
            'redirect_uri' => $this->mindsConfig->get('site_url') . self::OAUTH_TOKEN_REQUEST_CALLBACK,
            'scope' => join(
                " ",
                [
                    'tweet.read',
                    'tweet.write',
                    'users.read',
                    'offline.access',
                ]
            ),
            'state' => 'state',
            'code_challenge' => 'challenge',
            'code_challenge_method' => 'plain'
        ];
    }

    /**
     * @param string $authorizationCode
     * @return array
     */
    public function generateOAuthAccessToken(string $authorizationCode): array
    {
        $response = $this->httpClient->postAsync("2/oauth2/token", [
            RequestOptions::HEADERS => [
                "Content-Type" => '',
                "Authorization" => 'Basic ' . $this->getBearerToken(),
            ],
            RequestOptions::QUERY => [
                'grant_type' => 'authorization_code',
                'code' => $authorizationCode,
                'redirect_uri' => $this->mindsConfig->get('site_url') . self::OAUTH_TOKEN_REQUEST_CALLBACK,
                'code_verifier' => 'challenge'
            ]
        ])->then(function (Response $response): array {
            $details = json_decode($response->getBody()->getContents(), true);
            return [
                'accessToken' => $details['access_token'],
                'accessTokenExpiry' => strtotime("+" . $details['expires_in'] . " seconds"),
                'refreshToken' => $details['refresh_token'],
            ];
        });

        return $response->wait(true);
    }

    /**
     * @param string $refreshToken
     * @return array
     */
    public function refreshOAuthAccessToken(string $refreshToken): array
    {
        $response = $this->httpClient->postAsync("2/oauth2/token", [
            RequestOptions::HEADERS => [
                "Content-Type" => '',
                "Authorization" => 'Basic ' . $this->getBearerToken(),
            ],
            RequestOptions::QUERY => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $refreshToken
            ]
        ])->then(function (Response $response): array {
            $details = json_decode($response->getBody()->getContents(), true);
            return [
                'accessToken' => $details['access_token'],
                'accessTokenExpiry' => strtotime("+" . $details['expires_in'] . " seconds"),
                'refreshToken' => $details['refresh_token']
            ];
        });

        return $response->wait(true);
    }

    public function postTweet(TweetDTO $tweet, string $accessToken): bool
    {
        $response = $this->httpClient->postAsync(
            '2/tweets',
            [
                RequestOptions::HEADERS => [
                    'Content-Type' => 'application/json',
                    'Authorization' => "Bearer $accessToken"
                ],
                RequestOptions::BODY => json_encode($tweet)
            ]
        )->then(
            function (Response $response): bool {
                return true;
            },
            function (RequestException $e): void {
                throw $e;
            }
        );

        return $response->wait(true);
    }
}
