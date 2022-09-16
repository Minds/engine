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
            $this->mindsConfig->get('twitter')['client_id'],
            $this->mindsConfig->get('twitter')['client_secret'],
        );
        $this->connection->setApiVersion('2');
    }

    public function requestOAuthToken()
    {
        $this->connection->oauth2('oauth/request_token', [
            'oauth_callback' => urlencode($this->mindsConfig->get('site_url') . self::OAUTH_TOKEN_REQUEST_CALLBACK)
        ]);
    }
}
