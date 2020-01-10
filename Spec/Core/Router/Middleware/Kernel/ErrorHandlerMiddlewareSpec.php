<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Exception;
use Minds\Core\Router\Middleware\Kernel\ErrorHandlerMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class ErrorHandlerMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(ErrorHandlerMiddleware::class);
    }

    public function it_should_process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->setSentryEnabled(false)
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_catch_during_process_and_output_html(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $handler->handle($request)
            ->shouldBeCalled()
            ->willThrow(new Exception('PHPSpec'));

        $request->getAttribute('accept')
            ->shouldBeCalled()
            ->willReturn('html');

        $this
            ->setSentryEnabled(false)
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(HtmlResponse::class);
    }

    public function it_should_catch_during_process_and_output_json(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $handler->handle($request)
            ->shouldBeCalled()
            ->willThrow(new Exception('PHPSpec'));

        $request->getAttribute('accept')
            ->shouldBeCalled()
            ->willReturn('json');

        $this
            ->setSentryEnabled(false)
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(JsonResponse::class);
    }
}
