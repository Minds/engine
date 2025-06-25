<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Services\OidcAuthService;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Config\Config;
use Minds\Core\Security\Vault\VaultTransitService;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Core\Events\EventsDispatcher;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class OidcAuthServiceSpec extends ObjectBehavior
{
    private Collaborator $httpClientMock;
    private Collaborator $oidcUserServiceMock;
    private Collaborator $vaultTransitServiceMock;
    private Collaborator $eventDispatcherMock;

    public function let(
        Client $httpClientMock,
        OidcUserService $oidcUserServiceMock,
        SessionsManager $sessionsManagerMock,
        Config $configMock,
        VaultTransitService $vaultTransitServiceMock,
        EventsDispatcher $eventDispatcherMock,
    ) {
        $this->beConstructedWith($httpClientMock, $oidcUserServiceMock, $sessionsManagerMock, $configMock, $vaultTransitServiceMock, $eventDispatcherMock);
    
        $this->httpClientMock = $httpClientMock;
        $this->oidcUserServiceMock = $oidcUserServiceMock;
        $this->vaultTransitServiceMock = $vaultTransitServiceMock;
        $this->eventDispatcherMock = $eventDispatcherMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OidcAuthService::class);
    }

    public function it_should_fetch_openid_configuration_for_provider()
    {
        $provider = $this->buildOidcProvider();
        
        $this->shouldUseOpenIdConfigMock();

        $result = $this->getOpenIdConfiguration($provider);
        $result['issuer']->shouldBe('https://phpspec.local/');
    }

    public function it_should_get_auth_url()
    {
        $provider = $this->buildOidcProvider();
        
        $this->shouldUseOpenIdConfigMock();

        $result = $this->getAuthorizationUrl($provider, 'csrf-token');
        $result->shouldBe('https://phpspec.local/oauth/v2/authorize?response_type=code&client_id=phpspec&scope=openid+profile+email&state=csrf-token&providerId=1&redirect_uri=api%2Fv3%2Fauthenticate%2Foidc%2Fcallback');
    }

    public function it_should_perform_login()
    {
        $sub = 'sub';
        $kid = 'kid';

        $payload = [
            'iss' => 'http://example.minds.com',
            'aud' => 'http://example2.minds.com',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'sub' => $sub,
            'kid' => $kid,
            'preferred_username' => 'preferred_username',
            'given_name' => 'given_name',
            'email' => 'email',
        ];

        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($keyPair, $privkey);
        $keyInfo = openssl_pkey_get_details(
            openssl_pkey_get_public(
                openssl_pkey_get_details($keyPair)['key']
            )
        );

        $jwt = JWT::encode($payload, $privkey, 'RS256', $kid, ['alg' => 'RS256']);

        $provider = $this->buildOidcProvider();

        $this->vaultTransitServiceMock->decrypt('vault:v1:HB23vDusaOjgwk1+wuhMGcVXKC34PDwtTsSmoyZFGzIjhDyqiV57')
            ->shouldBeCalled()
            ->willReturn('secret');

        $this->shouldUseOpenIdConfigMock();

        $this->httpClientMock->post('https://phpspec.local/oauth/v2/token', [
            'form_params' => [
                'code' => 'auth-code',
                'client_id' => 'phpspec',
                'client_secret' => 'secret',
                'redirect_uri' => 'api/v3/authenticate/oidc/callback',
                'grant_type' => 'authorization_code',
            ]
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'access_token' => "DKpn8Y8oPS7OZsa-jiGdsSIrgp9mHhjvoKGHFyC4v6xNx5iomtP_w-kJmKc2Wg-hi_TO3yA",
                'token_type' => 'Bearer',
                'expires_in' => 43199,
                'id_token' => $jwt
            ]));

        $this->httpClientMock->get('https://phpspec.local/oauth/v2/keys')
            ->shouldBeCalled()
            ->willReturn(new JsonResponse(
                [
                    "keys" => [
                          [
                             "kty" => "RSA",
                             "use" => "sig",
                             "kid" => $kid,
                             "alg" => "RS256",
                             'kty' => 'RSA',
                             'n' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['n'])), '='),
                             'e' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['e'])), '='),
                        ]
                    ]
                ]
            ));

        $this->oidcUserServiceMock->getUserFromSub($sub, 1)
            ->willReturn(new User());

        $this->performAuthentication($provider, 'auth-code', 'csrf-token');
    }

    public function it_should_return_openid_configuration_from_event_hook()
    {
        $this->eventDispatcherMock->trigger('oidc:getOpenIdConfiguration', 'all', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn([
                'issuer' => 'fake'
            ]);
        $config = $this->getOpenIdConfiguration($this->buildOidcProvider());
        $config['issuer']->shouldBe('fake');
    }

    public function it_should_return_extended_scopes_from_event_hook()
    {
        $this->eventDispatcherMock->trigger('oidc:getOpenIdConfiguration', 'all', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldUseOpenIdConfigMock();

        $this->eventDispatcherMock->trigger('oidc:getScopes', 'all', Argument::type('array'), [])
            ->shouldBeCalled()
            ->willReturn([
                'fake.scope'
            ]);

        $this->eventDispatcherMock->trigger('oidc:getSupportedScopes', 'all', Argument::type('array'), [])
            ->shouldBeCalled()
            ->willReturn([
                'fake.scope'
            ]);

        $this->getAuthorizationUrl($this->buildOidcProvider(), '');
    }

    public function it_should_return_remote_user_from_event_hook()
    {
        $this->eventDispatcherMock->trigger('oidc:getOpenIdConfiguration', 'all', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(null);

        $this->shouldUseOpenIdConfigMock();

        $this->httpClientMock->post('https://phpspec.local/oauth/v2/token', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'access_token' => "DKpn8Y8oPS7OZsa-jiGdsSIrgp9mHhjvoKGHFyC4v6xNx5iomtP_w-kJmKc2Wg-hi_TO3yA",
                'token_type' => 'Bearer',
                'expires_in' => 43199,
            ]));

        $this->eventDispatcherMock->trigger('oidc:getRemoteUser', 'all', Argument::type('array'))
            ->shouldBeCalled()
            ->willReturn((object) [
                'sub' => 'id',
                'preferred_username' => 'test',
                'given_name' => 'test',
                'email' => 'test@test.com'
            ]);

        $this->vaultTransitServiceMock->decrypt('vault:v1:HB23vDusaOjgwk1+wuhMGcVXKC34PDwtTsSmoyZFGzIjhDyqiV57')
            ->shouldBeCalled()
            ->willReturn('secret');

        $this->performAuthentication($this->buildOidcProvider(), '', '');
    }

    public function it_should_perform_login_and_use_userinfo_endpoint()
    {
        $sub = 'sub';
        $kid = 'kid';

        $payload = [
            'iss' => 'http://example.minds.com',
            'aud' => 'http://example2.minds.com',
            'iat' => time(),
            'nbf' => time(),
            'exp' => time() + 3600,
            'sub' => $sub,
            'kid' => $kid,
            'preferred_username' => 'preferred_username',
            'given_name' => 'given_name',
            'email' => 'email',
        ];

        $keyPair = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($keyPair, $privkey);
        $keyInfo = openssl_pkey_get_details(
            openssl_pkey_get_public(
                openssl_pkey_get_details($keyPair)['key']
            )
        );

        $jwt = JWT::encode($payload, $privkey, 'RS256', $kid, ['alg' => 'RS256']);

        $provider = $this->buildOidcProvider();

        $this->vaultTransitServiceMock->decrypt('vault:v1:HB23vDusaOjgwk1+wuhMGcVXKC34PDwtTsSmoyZFGzIjhDyqiV57')
            ->shouldBeCalled()
            ->willReturn('secret');

        $this->httpClientMock->get('https://phpspec.local/.well-known/openid-configuration')
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'issuer' => 'https://phpspec.local/',
                'authorization_endpoint' => 'https://phpspec.local/oauth/v2/authorize',
                'token_endpoint' => 'https://phpspec.local/oauth/v2/token',
                'jwks_uri' => 'https://phpspec.local/oauth/v2/keys',
                'userinfo_endpoint' => 'https://phpspec.local/oauth/v2/userinfo',
                'scopes_supported' => [
                    'openid',
                    'profile',
                    'email'
                ]
            ]));

        $this->httpClientMock->post('https://phpspec.local/oauth/v2/token', [
            'form_params' => [
                'code' => 'auth-code',
                'client_id' => 'phpspec',
                'client_secret' => 'secret',
                'redirect_uri' => 'api/v3/authenticate/oidc/callback',
                'grant_type' => 'authorization_code',
            ]
        ])
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'access_token' => "DKpn8Y8oPS7OZsa-jiGdsSIrgp9mHhjvoKGHFyC4v6xNx5iomtP_w-kJmKc2Wg-hi_TO3yA",
                'token_type' => 'Bearer',
                'expires_in' => 43199,
                'id_token' => $jwt
            ]));

        $this->httpClientMock->get('https://phpspec.local/oauth/v2/keys')
            ->shouldBeCalled()
            ->willReturn(new JsonResponse(
                [
                    "keys" => [
                          [
                             "kty" => "RSA",
                             "use" => "sig",
                             "kid" => $kid,
                             "alg" => "RS256",
                             'kty' => 'RSA',
                             'n' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['n'])), '='),
                             'e' => rtrim(str_replace(['+', '/'], ['-', '_'], base64_encode($keyInfo['rsa']['e'])), '='),
                        ]
                    ]
                ]
            ));

        $this->httpClientMock->get('https://phpspec.local/oauth/v2/userinfo', [
            'headers' => [
                'Authorization' => 'Bearer DKpn8Y8oPS7OZsa-jiGdsSIrgp9mHhjvoKGHFyC4v6xNx5iomtP_w-kJmKc2Wg-hi_TO3yA',
            ]
        ])->shouldBeCalled()->willReturn(new JsonResponse([
            'sub' => "sub",
            'preferred_username' => 'preferred_username',
            'given_name' => 'given_name',
            'email' => 'email',
        ]));

        $this->oidcUserServiceMock->getUserFromSub($sub, 1)
            ->willReturn(new User());

        $this->performAuthentication($provider, 'auth-code', 'csrf-token');
    }

    //

    private function buildOidcProvider(): OidcProvider
    {
        return new OidcProvider(
            id: 1,
            name: 'phpspec oidc',
            issuer: 'https://phpspec.local/',
            clientId: 'phpspec',
            clientSecretCipherText: 'vault:v1:HB23vDusaOjgwk1+wuhMGcVXKC34PDwtTsSmoyZFGzIjhDyqiV57',
            configs: [],
        );
    }

    private function shouldUseOpenIdConfigMock(): void
    {
        $this->httpClientMock->get('https://phpspec.local/.well-known/openid-configuration')
            ->shouldBeCalled()
            ->willReturn(new JsonResponse([
                'issuer' => 'https://phpspec.local/',
                'authorization_endpoint' => 'https://phpspec.local/oauth/v2/authorize',
                'token_endpoint' => 'https://phpspec.local/oauth/v2/token',
                'jwks_uri' => 'https://phpspec.local/oauth/v2/keys',
                'scopes_supported' => [
                    'openid',
                    'profile',
                    'email'
                ]
            ]));
    }
}
