<?php

namespace Spec\Minds\Core\Router\Middleware;

use Exception;
use Minds\Core\Minds;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Prophecy\Prophet;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;
use Zend\Diactoros\ServerRequest;

class AdminMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(AdminMiddleware::class);
    }

    public function it_should_process(
        RequestHandlerInterface $handler,
        ResponseInterface $response,
    ) {
        // Prepare test mocks
        $userMock = (new Prophet())->prophesize(User::class);
        $userMock->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        $_SERVER['HTTP_X_XSRF_TOKEN'] = 'xsrftoken';

        $request = (new ServerRequest(serverParams: $_SERVER))
            ->withCookieParams(
                [
                    'XSRF-TOKEN' => 'xsrftoken'
                ]
            )
            ->withMethod("POST")
            ->withAttribute('_phpspec_user', $userMock->reveal());

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        // Action and Assert
        $this
            ->setAttributeName('_phpspec_user')
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_throw_unauthorized_if_no_user_during_process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) {
        $request->getAttribute('_phpspec_user')
            ->shouldBeCalled()
            ->willReturn(null);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_user')
            ->shouldThrow(UnauthorizedException::class)
            ->duringProcess($request, $handler);
    }

    public function it_should_throw_unauthorized_if_xsrf_check_fail_during_process(
        RequestHandlerInterface $handler,
        User $user
    ) {
        $request = (new ServerRequest())
            ->withCookieParams(
                [
                    'XSRF-TOKEN' => 'xsrftoken'
                ]
            )
            ->withMethod("POST")
            ->withAttribute('_phpspec_user', $user);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_user')
            ->shouldThrow(UnauthorizedException::class)
            ->duringProcess($request, $handler);
    }

    public function it_should_throw_forbidden_if_not_an_admin_during_process(
        RequestHandlerInterface $handler,
    ) {
        $user = new User();
        $user->removeAdmin();

        $request = (new ServerRequest())
            ->withMethod("GET")
            ->withAttribute('_phpspec_user', $user);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_user')
            ->shouldThrow(ForbiddenException::class)
            ->duringProcess($request, $handler);
    }
}
