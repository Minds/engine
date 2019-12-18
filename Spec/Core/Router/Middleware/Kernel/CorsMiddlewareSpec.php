<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Middleware\Kernel\CorsMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\EmptyResponse;

class CorsMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(CorsMiddleware::class);
    }

    public function it_should_process_and_return_empty_response_if_options(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) {
        $request->getMethod()
            ->shouldBeCalled()
            ->willReturn('OPTIONS');

        $request->getHeaderLine('Origin')
            ->shouldBeCalled()
            ->willReturn('https://phpspec.test');

        $this
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(EmptyResponse::class);
    }

    public function it_should_process_and_passthru_if_not_options(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $request->getMethod()
            ->shouldBeCalled()
            ->willReturn('GET');

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }
}
