<?php
namespace Minds\Core\Authentication\Oidc\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
use Minds\Core\Events\EventsDispatcher;
use Minds\Core\Security\Vault\VaultTransitService;
use Minds\Core\Security\XSRF;
use Minds\Core\Session;
use Minds\Core\Sessions\Manager as SessionsManager;

class OidcAuthService
{
    public function __construct(
        private Client $httpClient,
        private OidcUserService $oidcUserService,
        private SessionsManager $sessionsManager,
        private Config $config,
        private VaultTransitService $vaultTransitService,
        private EventsDispatcher $eventsDispatcher,
    ) {
        
    }

    /**
     * Returns the configuration of the openid provider
     */
    public function getOpenIdConfiguration(OidcProvider $provider): array
    {
        if ($eventResponse = $this->eventsDispatcher->trigger('oidc:getOpenIdConfiguration', 'all', [
            'provider' => $provider
        ])) {
            return $eventResponse;
        }

        $wellKnownConfigUrl = rtrim($provider->issuer, '/') . '/.well-known/openid-configuration';
        $response = $this->httpClient->get($wellKnownConfigUrl);

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Returns a url that the user should visit to kick start the oauth flow
     */
    public function getAuthorizationUrl(OidcProvider $provider, string $csrfStateToken): string
    {
        $openIdConfiguration = $this->getOpenIdConfiguration($provider);

        $authUrl = $openIdConfiguration['authorization_endpoint'];

        $scopes = array_intersect([
            'openid',
            'profile',
            'email',
            // Any additional scopes from the provider?
            ... $this->eventsDispatcher->trigger('oidc:getScopes', 'all', [
                'provider' => $provider
            ], []) ?: []
        ], $openIdConfiguration['scopes_supported'] ?? []);

        $queryParams = http_build_query([
            'response_type' => 'code',
            'client_id' => $provider->clientId,
            'scope' => implode(' ', $scopes),
            'state' => $csrfStateToken,
            'providerId' => $provider->id,
            'redirect_uri' =>  $this->getCallbackUrl(),
        ]);

        return $authUrl .= "?$queryParams";
    }

    /**
     * Performs authentication for a provider
     */
    public function performAuthentication(OidcProvider $provider, string $code, string $state): void
    {
        $openIdConfiguration = $this->getOpenIdConfiguration($provider);

        $response = $this->httpClient->post(
            uri: $openIdConfiguration['token_endpoint'],
            options: [
                'form_params' => [
                    'code' => $code,
                    'client_id' => $provider->clientId,
                    'client_secret' => $this->decryptClientSecret($provider->clientSecretCipherText),
                    'redirect_uri' => $this->getCallbackUrl(),
                    'grant_type' => 'authorization_code',
                ]
            ]
        );
        
        $data = json_decode($response->getBody()->getContents(), true);

        // Decode the id_token field

        // Tap into the OAuth integrations hook, if possible, instead of getting profile
        // data from the id token
        
        if ($eventResponse = $this->eventsDispatcher->trigger('oidc:getRemoteUser', 'all', [
            'provider' => $provider,
            'oauth_token_response' => $data,
        ])) {
            $jwtDecoded = $eventResponse;
        } else {
            $jwkKeySet = $this->getJwkKeySet($openIdConfiguration);

            $jwtDecoded = JWT::decode($data['id_token'], $jwkKeySet);
        }

        $sub = $jwtDecoded->sub;

        $preferredUsername = $jwtDecoded->preferred_username ?? (str_replace(' ', '', $jwtDecoded->name));

        // Check the 'sub' to see if this account is already linked
        $user = $this->oidcUserService->getUserFromSub($sub, $provider->id);

        // If not user, create one
        if (!$user) {
            // Create a new account
            $user = $this->oidcUserService->register(
                sub: $sub,
                providerId: $provider->id,
                preferredUsername: $preferredUsername,
                displayName: $jwtDecoded->given_name ?: $preferredUsername,
                email: $jwtDecoded->email,
            );
        }

        // For Confessionals only, if banned and trying to login, unban
        if ($this->config->get('tenant_id') === 47 && $user->isBanned()) {
            $this->oidcUserService->unbanUserFromSub($sub, $provider->id);
        }

        // Now do the login

        $this->sessionsManager->setUser($user);
        $this->sessionsManager->createSession();
        $this->sessionsManager->save(); // save to db and cookie

        if ($provider->issuer !== 'https://phpspec.local/') {
            \set_last_login($user);

            XSRF::setCookie(true);
        }
    }

    private function getJwkKeySet(array $openIdConfiguration): array
    {
        $jwksUrl = $openIdConfiguration['jwks_uri'];

        $response = $this->httpClient->get(uri: $jwksUrl);

        return JWK::parseKeySet(json_decode($response->getBody()->getContents(), true));
    }

    private function getCallbackUrl(): string
    {
        return $this->config->get('site_url') . 'api/v3/authenticate/oidc/callback';
    }

    /**
     * Returns the cipher text of the client secret that needs to be decrypted
     */
    private function decryptClientSecret(string $cipherText): string
    {
        // If the cipherText doesn't start with vault, we will assume this is pre-migrated and return the value
        if (strpos($cipherText, 'vault:', 0) === false) {
            return $cipherText;
        }
        return $this->vaultTransitService->decrypt($cipherText);
    }
}
