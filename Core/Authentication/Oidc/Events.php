<?php
namespace Minds\Core\Authentication\Oidc;

use Minds\Core\Config\Config;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;

class Events
{
    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private Config $config,
    ) {
        
    }

    public function register()
    {
        $this->eventsDispatcher->register('oidc:getOpenIdConfiguration', 'all', [$this, 'getOpenIdConfiguration']);
        // $this->eventsDispatcher->register('oidc:getScopes', 'all', [$this, 'getScopes']);
        // $this->eventsDispatcher->register('oidc:getRemoteUser', 'all', [$this, 'getRemoteUser']);
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
}
