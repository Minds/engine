<?php

namespace Spec\Minds\Core\Authentication\Oidc\Services;

use cinemr\sdk\client as SdkClient;
use GuzzleHttp\Client;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Services\OidcAuthService;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Config\Config;
use Minds\Core\Sessions\Manager as SessionsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response\JsonResponse;

class OidcAuthServiceSpec extends ObjectBehavior
{
    private Collaborator $httpClientMock;
    private Collaborator $oidcUserServiceMock;
    private Collaborator $sessionsManagerMock;
    private Collaborator $configMock;

    public function let(
        Client $httpClientMock,
        OidcUserService $oidcUserServiceMock,
        SessionsManager $sessionsManagerMock,
        Config $configMock,
    ) {
        $this->beConstructedWith($httpClientMock, $oidcUserServiceMock, $sessionsManagerMock, $configMock);
    
        $this->httpClientMock = $httpClientMock;
        $this->oidcUserServiceMock = $oidcUserServiceMock;
        $this->sessionsManagerMock = $sessionsManagerMock;
        $this->configMock = $configMock;
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

    // TODO: (https://gitlab.com/minds/engine/-/issues/2681) Failing because of expired token
    // public function it_should_perform_login()
    // {
    //     $provider = $this->buildOidcProvider();

    //     $this->shouldUseOpenIdConfigMock();

    //     $this->httpClientMock->post('https://phpspec.local/oauth/v2/token', [
    //         'form_params' => [
    //             'code' => 'auth-code',
    //             'client_id' => 'phpspec',
    //             'client_secret' => 'secret',
    //             'redirect_uri' => 'api/v3/authenticate/oidc/callback',
    //             'grant_type' => 'authorization_code',
    //         ]
    //     ])
    //         ->shouldBeCalled()
    //         ->willReturn(new JsonResponse([
    //             'access_token' => "DKpn8Y8oPS7OZsa-jiGdsSIrgp9mHhjvoKGHFyC4v6xNx5iomtP_w-kJmKc2Wg-hi_TO3yA",
    //             'token_type' => 'Bearer',
    //             'expires_in' => 43199,
    //             'id_token' => "eyJhbGciOiJSUzI1NiIsImtpZCI6IjI0MjEzMzA3MzQ2MDg1MTg3NCIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJodHRwczovL21pbmRzLXRlc3QtaGxyaXJnLnppdGFkZWwuY2xvdWQiLCJzdWIiOiIyNDE4NDkwOTM4OTc0NjM3MDIiLCJhdWQiOlsiMjQxODUwMDg2MDY4Mzk3OTc0QG1pbmRzLXRlc3QiLCIyNDE4NDk5MDg3ODM0OTA5NjYiXSwiZXhwIjoxNzAwODYzMjUyLCJpYXQiOjE3MDA4MjAwNTIsImF1dGhfdGltZSI6MTcwMDY2NjAyNywiYW1yIjpbInBhc3N3b3JkIiwicHdkIl0sImF6cCI6IjI0MTg1MDA4NjA2ODM5Nzk3NEBtaW5kcy10ZXN0IiwiY2xpZW50X2lkIjoiMjQxODUwMDg2MDY4Mzk3OTc0QG1pbmRzLXRlc3QiLCJhdF9oYXNoIjoiVm52cHlJSVI3QXAyOEtMRmdIcDQzdyIsImNfaGFzaCI6IjBJb3RyeUJWaFVyVFJoTjg3YW5nS0EiLCJuYW1lIjoiWklUQURFTCBBZG1pbiIsImdpdmVuX25hbWUiOiJaSVRBREVMIiwiZmFtaWx5X25hbWUiOiJBZG1pbiIsIm5pY2tuYW1lIjoibWFyayIsImxvY2FsZSI6ImVuIiwidXBkYXRlZF9hdCI6MTcwMDY1MDQ1MiwicHJlZmVycmVkX3VzZXJuYW1lIjoibWFyayIsImVtYWlsIjoibWFya0BtaW5kcy5jb20iLCJlbWFpbF92ZXJpZmllZCI6dHJ1ZX0.Y-OQA2yYFGLgD_iSm7SBpCFr5xtveSc4iReJfnCSyyl6mO-TNvucb2ctBCwP7rGwIVOYUULiQ6a5NbCymoETzo4MuxDkqhiHPh6jGyzl2PSGIl-D8MM1K-K45BNCkJt_6UetjkKNJabZQv9pTV9HepSjLxdqHcIWBemCZOUAiDdIEUPYjIz5BLIXo-A-jTX23-V2Ev9SBc3Re4u1AV9PmZCf7mACCtierkU-8FdZzd7WZ5sm2ogebQr78SsIvPP-BJIfByq3EnAxByaSKtbVxUBZ7a3gf5y3c8RkzNqc6tDpTZB4v8QugriKJgbaV1ZEJwO23cwxrLJaHwYuZChtIA",
    //         ]));

    //     $this->httpClientMock->get('https://phpspec.local/oauth/v2/keys')
    //         ->shouldBeCalled()
    //         ->willReturn(new JsonResponse(
    //             json_decode('{"keys":[{"use":"sig","kty":"RSA","kid":"241982714809475668","alg":"RS256","n":"x5KYadaxUHpJHx-g5_cn_xT2faKXIvACw3atLnT448kMkAaOLjneIX8VPKcw4xDtIhrZhwRtHeo6O1aK1-Knikf244-OoQcPto1bCdmnNSxPn_pHMOodcUMKmyHaCU1FE2fd9c76Ga-aueJhQV-H83cEszoFo7qvdnSClk3dFwyaixHEdVxKgRpjbGkbY5nD4HVSF_P8P-7xS7xLlgdF_yEKgWUSKQbLbEH7vLPONal8g9vrGyaw9ynU_gCJL_GhIARxNxoP1ecfCshypYsy2sV3YUQ5lpieoi6r2avFc1HTQgIXtPXEwmQV7gJ8MsbA3lCHqx8aelRdmuMj_xHpFw","e":"AQAB"},{"use":"sig","kty":"RSA","kid":"242133073460851874","alg":"RS256","n":"wf1oHlg5_9y4lLKQwAX4rFS1mlXGrDdjB56TZP3lWok2m8OhNpXP8ilOmF0fYAUSfQgBiV0uudf5opGPKpuccp6xfXBdMIEd11JNvTRF0rYJQ-zeFaekJbNcElxOI9OF4vJHOVCCa0527_LcuChApSZ6ShvFMz4PcDOP13ZRIvy-IWa94pIphe9pZobuqpW7r-5E5yzLmwjBYklxD1dz6WEgwXYUcoNI25y_AlvXvQJEIzmBtUPofU_LrWOTRjuQgoiUgoW-Uo908Jb_acZqc0SQHSpR0LKHvtkbjQrd6Xs4P4zhw7KcNrsD0Mf-4oap_zYqauolRomeN3RmVct54w","e":"AQAB"}]}', true)
    //         ));

    //     $this->oidcUserServiceMock->getUserFromSub('241849093897463702', 1)
    //         ->willReturn(new User());

    //     $this->performAuthentication($provider, 'auth-code', 'csrf-token');
    // }

    //

    private function buildOidcProvider(): OidcProvider
    {
        return new OidcProvider(
            id: 1,
            name: 'phpspec oidc',
            issuer: 'https://phpspec.local/',
            clientId: 'phpspec',
            clientSecret: 'secret'
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
                'jwks_uri' => 'https://phpspec.local/oauth/v2/keys'
            ]));
    }
}
