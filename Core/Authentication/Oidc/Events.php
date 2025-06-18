<?php
namespace Minds\Core\Authentication\Oidc;

use GuzzleHttp\Client;
use Minds\Core\Config\Config;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;

class Events
{
    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private Config $config,
        private Client $httpClient,
    ) {
        
    }

    public function register()
    {
        $this->eventsDispatcher->register('oidc:getOpenIdConfiguration', 'all', [$this, 'getOpenIdConfiguration']);
        $this->eventsDispatcher->register('oidc:getScopes', 'all', [$this, 'getScopes']);
        $this->eventsDispatcher->register('oidc:getSupportedScopes', 'all', [$this, 'getSupportedScopes']);
        $this->eventsDispatcher->register('oidc:getRemoteUser', 'all', [$this, 'getRemoteUser']);
    }

    
    /**
     * Tap into the the OidcProvider to provide the token and authorization endponits
     */
    public function getOpenIdConfiguration(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];
        $providerUrl = rtrim($oidcProvider->issuer, '/');

        switch ($providerUrl) {
            case "https://facebook.com":
            case "https://www.facebook.com":
                $event->setResponse([
                    'token_endpoint' => 'https://graph.facebook.com/v11.0/oauth/access_token',
                    'authorization_endpoint' => 'https://facebook.com/dialog/oauth/',
                    'jwks_uri' => 'https://www.facebook.com/.well-known/oauth/openid/jwks/',
                    'scopes_supported' => [
                        'openid',
                        'email',
                    ],
                ]);
                break;
        }
    }

    /**
     * Tap into the OidcProvider to provide the available scopes
     */
    public function getScopes(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];
        $providerUrl = rtrim($oidcProvider->issuer, '/');

        switch ($providerUrl) {
            case "https://discord.com":
                $event->setResponse([
                    'identify',
                    'guilds',
                ]);
                break;
        }
    }

    /**
     * Tap into the OidcProvider to provide the supported scopes
     */
    public function getSupportedScopes(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];
        $providerUrl = rtrim($oidcProvider->issuer, '/');

        switch ($providerUrl) {
            case "https://discord.com":
                $event->setResponse([
                    'email',
                    'openid',
                    'identify',
                    'guilds',
                ]);
                break;
        }
    }

    public function getRemoteUser(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];
        $providerUrl = rtrim($oidcProvider->issuer, '/');

        $openIdConfiguration = $event->getParameters()['openid_configuration'];

        $oauthData = $event->getParameters()['oauth_token_response'];

        switch ($providerUrl) {
            case "https://discord.com":
                // Get the user info
                $userInfoResponse = $this->httpClient->get(
                    uri: $openIdConfiguration['userinfo_endpoint'],
                    options: [
                        'headers' => [
                            'Authorization' => 'Bearer ' . $oauthData['access_token'],
                        ]
                    ]
                );
                $userInfoBody = $userInfoResponse->getBody()->getContents();
                $userInfo = json_decode($userInfoBody);

                // If a server restriction was configured, check the users list of servers
                if ($oidcProvider->configs['server_id'] ?? null) {
                    // Now check that the user
                    $serverListResponse = $this->httpClient->get(
                        uri: 'https://discord.com/api/users/@me/guilds',
                        options: [
                            'headers' => [
                                'Authorization' => 'Bearer ' . $oauthData['access_token'],
                            ]
                        ]
                    );
                    $serverListBody = $serverListResponse->getBody()->getContents();
                    $serverList = json_decode($serverListBody);
                    $serverIds = array_map(fn ($server) => $server->id, $serverList);
                    if (!in_array($oidcProvider->configs['server_id'], $serverIds, true)) {
                        throw new ForbiddenException("You are not member of the discord server.");
                    }
                }

                $event->setResponse($userInfo);

                break;
        }
    }

}
