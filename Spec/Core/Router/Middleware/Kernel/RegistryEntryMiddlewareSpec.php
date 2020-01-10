<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Di\Di;
use Minds\Core\Di\Ref as DiRef;
use Minds\Core\Router\Middleware\Kernel\RegistryEntryMiddleware;
use Minds\Core\Router\RegistryEntry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RegistryEntryMiddlewareSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(RegistryEntryMiddleware::class);
    }

    public function it_should_process_and_passthru_if_no_entry(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $request->getAttribute('_phpspec_router-registry-entry')
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->setAttributeName('_phpspec_router-registry-entry')
            ->process($request, $handler);
    }

    public function it_should_process_di_bindings(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        RegistryEntry $registryEntry,
        DiRef $diRef,
        UriInterface $uri
    ) {
        $request->getAttribute('_phpspec_router-registry-entry')
            ->shouldBeCalled()
            ->willReturn($registryEntry);

        $registryEntry->getBinding()
            ->shouldBeCalled()
            ->willReturn($diRef);

        $request->getUri()
            ->shouldBeCalled()
            ->willReturn($uri);

        $uri->getPath()
            ->shouldBeCalled()
            ->willReturn('/phpspec/1000/edit');

        $registryEntry->extract('/phpspec/1000/edit')
            ->shouldBeCalled()
            ->willReturn(['id' => '1000']);

        $providerId = static::class . 'Provider';

        $diRef->getProvider()
            ->shouldBeCalled()
            ->willReturn($providerId);

        $diRef->getMethod()
            ->shouldBeCalled()
            ->willReturn('test');

        Di::_()->bind($providerId, function () use ($response) {
            return (new class {
                protected $response;

                public function setResponse($response)
                {
                    $this->response = $response;
                    return $this;
                }

                public function test()
                {
                    return $this->response;
                }
            })->setResponse($response->getWrappedObject());
        });

        $request->withAttribute('parameters', ['id' => '1000'])
            ->shouldBeCalled()
            ->willReturn($request);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_router-registry-entry')
            ->process($request, $handler)
            ->shouldReturn($response);

        Di::_()->bind($providerId, function () {
            return false; // Release closure bindings
        });
    }

    public function it_should_process_callable(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        RegistryEntry $registryEntry,
        UriInterface $uri
    ) {
        $request->getAttribute('_phpspec_router-registry-entry')
            ->shouldBeCalled()
            ->willReturn($registryEntry);

        $binding = function () use ($response) {
            return $response->getWrappedObject();
        };

        $registryEntry->getBinding()
            ->shouldBeCalled()
            ->willReturn($binding);

        $request->getUri()
            ->shouldBeCalled()
            ->willReturn($uri);

        $uri->getPath()
            ->shouldBeCalled()
            ->willReturn('/phpspec/1000/edit');

        $registryEntry->extract('/phpspec/1000/edit')
            ->shouldBeCalled()
            ->willReturn(['id' => '1000']);

        $request->withAttribute('parameters', ['id' => '1000'])
            ->shouldBeCalled()
            ->willReturn($request);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this
            ->setAttributeName('_phpspec_router-registry-entry')
            ->process($request, $handler)
            ->shouldReturn($response);
    }
}
