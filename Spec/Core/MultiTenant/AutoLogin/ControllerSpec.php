<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\AutoLogin;

use Minds\Core\MultiTenant\AutoLogin\Controller;
use Minds\Core\MultiTenant\AutoLogin\AutoLoginService;
use PhpSpec\ObjectBehavior;
use Zend\Diactoros\ServerRequest;
use Zend\Diactoros\Response\RedirectResponse;

class ControllerSpec extends ObjectBehavior
{
    /** @var AutoLoginService */
    protected $autoLoginServiceMock;

    public function let(AutoLoginService $autoLoginServiceMock)
    {
        $this->autoLoginServiceMock = $autoLoginServiceMock;
        $this->beConstructedWith($autoLoginServiceMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Controller::class);
    }

    // postLogin

    public function it_should_handle_post_login_without_redirect_path(ServerRequest $request)
    {
        $jwtToken = 'jwt_token';

        $request->getParsedBody()->willReturn([
            'jwt_token' => $jwtToken,
        ]);

        $this->autoLoginServiceMock->performLogin($jwtToken)->shouldBeCalled();

        $response = $this->postLogin($request);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike(['/network/admin']);
    }

    public function it_should_handle_post_login_with_valid_redirect_path(ServerRequest $request)
    {
        $jwtToken = 'jwt_token';
        $redirectPath = '/valid/path';

        $request->getParsedBody()->willReturn([
            'jwt_token' => $jwtToken,
            'redirect_path' => $redirectPath,
        ]);

        $this->autoLoginServiceMock->performLogin($jwtToken)->shouldBeCalled();

        $response = $this->postLogin($request);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike([$redirectPath]);
    }

    public function it_should_handle_post_login_with_invalid_redirect_path(ServerRequest $request)
    {
        $jwtToken = 'jwt_token';
        $redirectPath = 'https://example.minds.com/test';

        $request->getParsedBody()->willReturn([
            'jwt_token' => $jwtToken,
            'redirect_path' => $redirectPath,
        ]);

        $this->autoLoginServiceMock->performLogin($jwtToken)->shouldBeCalled();

        $response = $this->postLogin($request);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike(['/network/admin']);
    }

    // getLogin

    public function it_should_handle_get_login_without_redirect_path(ServerRequest $request)
    {
        $jwtToken = 'jwt_token';

        $request->getQueryParams()->willReturn([
            'token' => $jwtToken,
        ]);

        $this->autoLoginServiceMock->performLogin($jwtToken)->shouldBeCalled();

        $response = $this->getLogin($request);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike(['/network/admin']);
    }

    public function it_should_handle_get_login_with_valid_redirect_path(ServerRequest $request)
    {
        $jwtToken = 'jwt_token';
        $redirectPath = '/valid/path';

        $request->getQueryParams()->willReturn([
            'token' => $jwtToken,
            'redirect_path' => $redirectPath,
        ]);

        $this->autoLoginServiceMock->performLogin($jwtToken)->shouldBeCalled();

        $response = $this->getLogin($request);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike([$redirectPath]);
    }

    public function it_should_handle_get_login_with_invalid_redirect_path(ServerRequest $request)
    {
        $jwtToken = 'jwt_token';
        $redirectPath = 'https://example.minds.com/test';

        $request->getQueryParams()->willReturn([
            'token' => $jwtToken,
            'redirect_path' => $redirectPath,
        ]);

        $this->autoLoginServiceMock->performLogin($jwtToken)->shouldBeCalled();

        $response = $this->getLogin($request);
        $response->shouldBeAnInstanceOf(RedirectResponse::class);
        $response->getStatusCode()->shouldBe(302);
        $response->getHeader('Location')->shouldBeLike(['/network/admin']);
    }
}
