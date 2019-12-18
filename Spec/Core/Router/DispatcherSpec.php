<?php

namespace Spec\Minds\Core\Router;

use Minds\Core\Router\Dispatcher;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class DispatcherSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Dispatcher::class);
    }

    public function it_should_pipe(
        MiddlewareInterface $middleware
    ) {
        $this
            ->pipe($middleware)
            ->shouldReturn($this);
    }

    public function it_should_handle(
        MiddlewareInterface $middleware1,
        MiddlewareInterface $middleware2,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $middleware1->process($request, $this)
            ->shouldBeCalled()
            ->willReturn($response);

        $middleware2->process(Argument::cetera())
            ->shouldNotBeCalled();

        $this
            ->pipe($middleware1)
            ->pipe($middleware2)
            ->handle($request)
            ->shouldReturn($response);
    }

    public function it_should_handle_an_empty_stack(
        MiddlewareInterface $fallbackMiddleware,
        ServerRequestInterface $request,
        ResponseInterface $response
    ) {
        $this->beConstructedWith($fallbackMiddleware);

        $fallbackMiddleware->process($request, $this)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->handle($request)
            ->shouldReturn($response);
    }
}
