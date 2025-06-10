<?php

namespace Spec\Minds\Core\Router\Middleware;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggedInMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(LoggedInMiddleware::class);
    }


    public function it_should_process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        User $user
    ) {
        $request->getAttribute('_phpspec_user')
            ->shouldBeCalled()
            ->willReturn($user);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->setAttributeName('_phpspec_user')
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_throw_forbidden_if_no_user_during_process(
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
            ->shouldThrow(ForbiddenException::class)
            ->duringProcess($request, $handler);
    }

}
