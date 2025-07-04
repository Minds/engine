<?php

namespace Spec\Minds\Core\Authentication\Oidc\Controllers;

use Minds\Core\Authentication\Oidc\Controllers\OidcPsr7Controller;
use Minds\Core\Authentication\Oidc\Models\OidcProvider;
use Minds\Core\Authentication\Oidc\Services\OidcAuthService;
use Minds\Core\Authentication\Oidc\Services\OidcProvidersService;
use Minds\Core\Authentication\Oidc\Services\OidcUserService;
use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Exceptions\UserErrorException;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;
use Zend\Diactoros\Response\RedirectResponse;
use Zend\Diactoros\ServerRequest;

class OidcPsr7ControllerSpec extends ObjectBehavior
{
    private Collaborator $oidcAuthServiceMock;
    private Collaborator $oidcProvidersServiceMock;
    private Collaborator $oidcUserServiceMock;

    public function let(OidcAuthService $oidcAuthServiceMock, OidcProvidersService $oidcProvidersServiceMock, OidcUserService $oidcUserServiceMock)
    {
        $this->beConstructedWith($oidcAuthServiceMock, $oidcProvidersServiceMock, $oidcUserServiceMock);

        $this->oidcAuthServiceMock = $oidcAuthServiceMock;
        $this->oidcProvidersServiceMock = $oidcProvidersServiceMock;
        $this->oidcUserServiceMock = $oidcUserServiceMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(OidcPsr7Controller::class);
    }

    public function it_should_redirect_to_oidc_provider_login(ServerRequest $requestMock)
    {
        $requestMock->getQueryParams()->willReturn([
            'providerId' => 1,
        ]);

        $this->oidcProvidersServiceMock->getProviderById(1)
            ->shouldBeCalled()
            ->willReturn(new OidcProvider(
                id: 1,
                name: 'PHPSpec',
                issuer: 'https://phpspec.local',
                clientId: 'phpspec',
                clientSecretCipherText: 'secret',
                configs: [],
            ));

        $this->oidcAuthServiceMock->getAuthorizationUrl(Argument::type(OidcProvider::class), Argument::type('string'))
            ->willReturn('https://phpspec.local/');

        $response = $this->oidcLogin($requestMock);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
    }

    public function it_should_perform_authentication_on_callback(ServerRequest $requestMock)
    {
        $requestMock->getQueryParams()->willReturn([
            'code' => 'auth-code',
            'state' => 'csrf-token',
        ]);

        $requestMock->getCookieParams()->willReturn([
            'oidc_provider_id' => '1',
            'oidc_csrf_state' => 'csrf-token',
        ]);

        $requestMock->getAttribute(RequestAttributeEnum::CSP_NONCE)
            ->willReturn('nonce');

        $this->oidcProvidersServiceMock->getProviderById(1)
            ->willReturn(new OidcProvider(
                id: 1,
                name: 'PHPSpec',
                issuer: 'https://phpspec.local',
                clientId: 'phpspec',
                clientSecretCipherText: 'secret',
                configs: [],
            ));

        $this->oidcAuthServiceMock->performAuthentication(Argument::type(OidcProvider::class), 'auth-code', 'csrf-token')
            ->shouldBeCalled();

        $response = $this->oidcCallback($requestMock);
        $response->shouldBeAnInstanceOf(HtmlResponse::class);
    }

    public function it_should_throw_error_if_csrf_fails(ServerRequest $requestMock)
    {
        $requestMock->getQueryParams()->willReturn([
            'code' => 'auth-code',
            'state' => 'csrf-token-will-fail',
        ]);

        $requestMock->getCookieParams()->willReturn([
            'oidc_provider_id' => '1',
            'oidc_csrf_state' => 'csrf-token',
        ]);

        $this->oidcProvidersServiceMock->getProviderById(1)
            ->willReturn(new OidcProvider(
                id: 1,
                name: 'PHPSpec',
                issuer: 'https://phpspec.local',
                clientId: 'phpspec',
                clientSecretCipherText: 'secret',
                configs: [],
            ));

        $this->oidcAuthServiceMock->performAuthentication(Argument::type(OidcProvider::class), 'auth-code', 'csrf-token')
            ->shouldNotBeCalled();

        $this->shouldThrow(UserErrorException::class)->duringOidcCallback($requestMock);
    }

    public function it_should_ban_user_when_called(ServerRequest $requestMock)
    {
        $requestMock->getAttribute('parameters')->willReturn([
            'sub' => 'sub',
            'providerId' => 1,
        ]);

        $this->oidcUserServiceMock->suspendUserFromSub('sub', 1)
            ->shouldBeCalled()
            ->willReturn(true);

        $response = $this->suspendUser($requestMock);
        $response->shouldBeAnInstanceOf(JsonResponse::class);
    }
}
