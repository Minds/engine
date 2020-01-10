<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Middleware\Kernel\EmptyResponseMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class EmptyResponseMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(EmptyResponseMiddleware::class);
    }

    public function it_should_process_and_return_an_html_response(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) {
        $request->getAttribute('accept')
            ->shouldBeCalled()
            ->willReturn('html');

        $this
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(HtmlResponse::class);
    }

    public function it_should_process_and_return_a_json_response(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) {
        $request->getAttribute('accept')
            ->shouldBeCalled()
            ->willReturn('json');

        $this
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(JsonResponse::class);
    }
}
