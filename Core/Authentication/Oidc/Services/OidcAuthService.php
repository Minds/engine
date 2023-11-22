<?php
namespace Minds\Core\Authentication\Oidc\Services;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Config\Config;
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
    ) {
        
    }

    /**
     * Returns the configuration of the openid provider
     */
    public function getOpenIdConfiguration(OidcProvider $provider): array
    {
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

        $queryParams = http_build_query([
            'response_type' => 'code',
            'client_id' => $provider->clientId,
            'scope' => 'openid profile email',
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
                    'client_secret' => $provider->clientSecret,
                    'redirect_uri' => $this->getCallbackUrl(),
                    'grant_type' => 'authorization_code',
                ]
            ]
        );
        
        $data = json_decode($response->getBody()->getContents(), true);

        // Decode the id_token field

        $jwkKeySet = $this->getJwkKeySet($openIdConfiguration);

        $jwtDecoded = JWT::decode($data['id_token'], $jwkKeySet);

        $sub = $jwtDecoded->sub;

        // Check the 'sub' to see if this account is already linked
        $user = $this->oidcUserService->getUserFromSub($sub, 1);

        // If not user, create one
        if (!$user) {
            // Create a new account
            $user = $this->oidcUserService->register(
                sub: $sub,
                providerId: $provider->id,
                preferredUsername: $jwtDecoded->preferred_username,
                displayName: $jwtDecoded->given_name ?: $jwtDecoded->preferred_username,
                email: $jwtDecoded->email,
            );
        }

        // Now do the login

        $this->sessionsManager->setUser($user);
        $this->sessionsManager->createSession();
        $this->sessionsManager->save(); // save to db and cookie

        \set_last_login($user);

        Session::generateJWTCookie($this->sessionsManager->getSession());
        XSRF::setCookie(true);
    }

    private function getJwkKeySet(array $openIdConfiguration): array
    {
        //$openIdConfiguration = $this->getOpenIdConfiguration($provider);
        $jwksUrl = $openIdConfiguration['jwks_uri'];

        $response = $this->httpClient->get(uri: $jwksUrl);

        return JWK::parseKeySet(json_decode($response->getBody()->getContents(), true));
    }

    private function getCallbackUrl(): string
    {
        return $this->config->get('site_url') . 'api/v3/authenticate/oidc/callback';
    }
}
