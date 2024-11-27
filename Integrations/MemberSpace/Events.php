<?php
namespace Minds\Integrations\MemberSpace;

use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Events\Event;
use Minds\Core\Events\EventsDispatcher;
use stdClass;

class Events
{
    public function __construct(
        private EventsDispatcher $eventsDispatcher,
        private Config $config,
        private ?MemberSpaceService $service = null,
    ) {
        
    }

    public function register()
    {
        $this->eventsDispatcher->register('oidc:getOpenIdConfiguration', 'all', [$this, 'getOpenIdConfiguration']);
        $this->eventsDispatcher->register('oidc:getScopes', 'all', [$this, 'getScopes']);
        $this->eventsDispatcher->register('oidc:getRemoteUser', 'all', [$this, 'getRemoteUser']);
    }

    /**
     * Tap into the the OidcProvider to provide the token and authorization endponits
     */
    public function getOpenIdConfiguration(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];

        if (!$this->isMemberSpaceOAuthProvider($oidcProvider)) {
            return; // Not out concern, continue
        }

        $providerUrl = rtrim($oidcProvider->issuer, '/');
        $event->setResponse([
            'token_endpoint' => $providerUrl . '/oauth/token',
            'authorization_endpoint' => $providerUrl . '/oauth/authorize',
            'scopes_supported' => [
                'read.account'
            ],
        ]);
    }

    /**
     * MemberSpace requires the 'read.account' scope.
     */
    public function getScopes(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];

        if (!$this->isMemberSpaceOAuthProvider($oidcProvider)) {
            return; // Not out concern, continue
        }

        $event->setResponse([ 'read.account' ]);
    }

    /**
     * When a user enters credentials to the login form, we will first try these
     * against MemberSpace. If there is a failure we will try a local login.
     */
    public function getRemoteUser(Event $event): void
    {
        /** @var OidcProvider */
        $oidcProvider = $event->getParameters()['provider'];
        $oauthTokenResponse = $event->getParameters()['oauth_token_response'];

        if (!$this->isMemberSpaceOAuthProvider($oidcProvider)) {
            return; // Not out concern, continue
        }

        $memberSpaceProfile = $this->getMemberSpaceService()->getProfile($oauthTokenResponse['access_token']);

        $nameParts = explode(' ', $memberSpaceProfile->name);

        $fakeJwtObj = new \stdClass();
        $fakeJwtObj->sub = $memberSpaceProfile->id;
        $fakeJwtObj->given_name = $memberSpaceProfile->name;
        $fakeJwtObj->preferred_username = ucfirst($nameParts[0]) . ucfirst(substr($nameParts[1], 0, 1));
        $fakeJwtObj->email = $memberSpaceProfile->email;

        $event->setResponse($fakeJwtObj);
    }
    
    private function isMemberSpaceOAuthProvider(OidcProvider $provider): bool
    {
        return strpos($provider->issuer, '.memberspace.com') !== false;
    }
    
    protected function getMemberSpaceService(): MemberSpaceService
    {
        return $this->service ??= Di::_()->get(MemberSpaceService::class);
    }

}
