<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Middleware\Kernel\ContentNegotiationMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ContentNegotiationMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ContentNegotiationMiddleware::class);
    }

    public function it_should_process_and_mark_as_json(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $request->getHeader('Accept')
            ->shouldBeCalled()
            ->willReturn(['text/json; utf=8,application/json']);

        $request
            ->withAttribute('accept', 'json')
            ->shouldBeCalled()
            ->willReturn($request);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_process_and_mark_as_html(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $request->getHeader('Accept')
            ->shouldBeCalled()
            ->willReturn(['text/html; utf=8', 'application/xhtml+xml']);

        $request
            ->withAttribute('accept', 'html')
            ->shouldBeCalled()
            ->willReturn($request);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_process_and_leave_unmarked(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $request->getHeader('Accept')
            ->shouldBeCalled()
            ->willReturn(['image/png']);

        $request
            ->withAttribute('accept', Argument::cetera())
            ->shouldNotBeCalled();

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }
}
