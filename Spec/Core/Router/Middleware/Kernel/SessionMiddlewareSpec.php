<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Router\Middleware\Kernel\SessionMiddleware;
use Minds\Core\Sessions\Manager;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddlewareSpec extends ObjectBehavior
{
    protected Collaborator $sessionManagerMock;

    public function let(
        Manager $sessionManagerMock
    ) {
        $this->beConstructedWith($sessionManagerMock);
        $this->sessionManagerMock = $sessionManagerMock;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SessionMiddleware::class);
    }

    public function it_should_throw_unauthorized_exception_if_bad_credentials(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) {
        $request->getAttribute('_user')
            ->shouldBeCalled()
            ->willReturn(null);

        $this->sessionManagerMock->withRouterRequest($request)
            ->willThrow(new UnauthorizedException());

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_user')
            ->shouldThrow(UnauthorizedException::class)
            ->duringProcess($request, $handler);
    }
}
