<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Exception;
use Minds\Core\Router\Middleware\Kernel\ErrorHandlerMiddleware;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\JsonResponse;

class ErrorHandlerMiddlewareSpec extends ObjectBehavior
{
    /** @var LoggerInterface */
    protected $logger;

    public function let(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;

        $this->beConstructedWith($logger);
    }

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
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_catch_during_process_and_output_html(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $exception = new Exception('PHPSpec');

        $handler->handle($request)
            ->shouldBeCalled()
            ->willThrow($exception);

        $this->logger->critical($exception, ['exception' => $exception])
            ->shouldBeCalled()
            ->willReturn(null);

        $request->getAttribute('accept')
            ->shouldBeCalled()
            ->willReturn('html');

        $this
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(HtmlResponse::class);
    }

    public function it_should_catch_during_process_and_output_json(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $exception = new Exception('PHPSpec');

        $handler->handle($request)
            ->shouldBeCalled()
            ->willThrow($exception);

        $this->logger->critical($exception, ['exception' => $exception])
            ->shouldBeCalled()
            ->willReturn(null);

        $request->getAttribute('accept')
            ->shouldBeCalled()
            ->willReturn('json');

        $this
            ->process($request, $handler)
            ->shouldBeAnInstanceOf(JsonResponse::class);
    }
}
