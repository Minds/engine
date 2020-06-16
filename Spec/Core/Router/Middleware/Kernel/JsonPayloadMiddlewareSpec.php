<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use GuzzleHttp\Psr7\Stream;
use Minds\Core\Router\Middleware\Kernel\JsonPayloadMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class JsonPayloadMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(JsonPayloadMiddleware::class);
    }

    public function it_should_process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        Stream $stream,
        ResponseInterface $response
    ) {
        $request->getHeader('Content-Type')
            ->shouldBeCalled()
            ->willReturn(['text/json']);

        $request->getBody()
            ->shouldBeCalled()
            ->willReturn($stream);

        $stream->getContents()
            ->shouldBeCalled()
            ->willReturn(json_encode(['phpspec' => 1]));

        $request->withParsedBody(['phpspec' => 1])
            ->shouldBeCalled()
            ->willReturn($request);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }
}
