<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Dispatcher;
use Minds\Core\Router\Middleware\Kernel\RequestHandlerMiddleware;
use Minds\Core\Router\RegistryEntry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RequestHandlerMiddleware::class);
    }

    public function it_should_process_and_passthru_if_no_handler(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $request->getAttribute('_phpspec_request-handler')
            ->shouldBeCalled()
            ->willReturn(null);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->setAttributeName('_phpspec_request-handler')
            ->process($request, $handler);
    }

    public function it_should_process_and_dispatch_using_registry_entry(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        RegistryEntry $registryEntry,
        MiddlewareInterface $middleware
    ) {
        $request->getAttribute('_phpspec_request-handler')
            ->shouldBeCalled()
            ->willReturn($registryEntry);

        $registryEntry->getMiddleware()
            ->shouldBeCalled()
            ->willReturn([$middleware]);

        $request->withAttribute('_router-registry-entry', $registryEntry)
            ->shouldBeCalled()
            ->willReturn($request);

        $middleware->process($request, Argument::type(Dispatcher::class))
            ->shouldBeCalled()
            ->willReturn($response);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_request-handler')
            ->process($request, $handler);
    }

    public function it_should_process_and_dispatch_using_closure(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        RegistryEntry $registryEntry,
        MiddlewareInterface $middleware
    ) {
        $closure = function () use ($response) {
            return $response->getWrappedObject();
        };

        $request->getAttribute('_phpspec_request-handler')
            ->shouldBeCalled()
            ->willReturn($closure);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_request-handler')
            ->process($request, $handler);
    }
}
