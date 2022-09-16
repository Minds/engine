<?php

declare(strict_types=1);

namespace Minds\Core\Twitter\Client;

use Abraham\TwitterOAuth\TwitterOAuth;
use Minds\Core\Config\Config as MindsConfig;
use Minds\Core\Di\Di;

/**
 *
 */
class TwitterClient implements TwitterClientInterface
{
    private const OAUTH_TOKEN_REQUEST_CALLBACK = 'api/v3/twitter/oauth';

    /**
     * @param TwitterOAuth|null $connection
     * @param MindsConfig|null $mindsConfig
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function __construct(
        private ?TwitterOAuth $connection = null,
        private ?MindsConfig $mindsConfig = null
    ) {
        $this->mindsConfig ??= Di::_()->get('Config');

        $this->connection ??= new TwitterOAuth(
            $this->mindsConfig->get('twitter')['api_key'],
            $this->mindsConfig->get('twitter')['api_secret'],
            $this->mindsConfig->get('twitter')['access_token'],
            $this->mindsConfig->get('twitter')['access_token_secret'],
        );
        $this->connection->setApiVersion('2');
    }

    public function requestOAuthTokenUrlDetails(): array
    {
        return [
            'response_type' => 'code',
            'client_id' => $this->mindsConfig->get('twitter')['client_id'],
            'redirect_uri' => $this->mindsConfig->get('site_url') . self::OAUTH_TOKEN_REQUEST_CALLBACK,
            'scope' => urlencode(
                join(
                    " ",
                    [
                        'tweet.read',
                        'tweet.write',
                        'offline.access',
                    ]
                )
            ),
            'state' => 'state',
            'code_challenge' => 'challenge',
            'code_challenge_method' => 'plain'
        ];
    }
}
