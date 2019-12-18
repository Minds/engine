<?php

namespace Spec\Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Middleware\Kernel\RouteResolverMiddleware;
use Minds\Core\Router\Registry;
use Minds\Core\Router\RegistryEntry;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteResolverMiddlewareSpec extends ObjectBehavior
{
    /** @var Registry */
    protected $registry;

    public function let(
        Registry $registry
    ) {
        $this->registry = $registry;

        $this->beConstructedWith($registry);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(RouteResolverMiddleware::class);
    }

    public function it_should_process_using_registry_entry(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response,
        RegistryEntry $registryEntry,
        UriInterface $uri
    ) {
        $request->getMethod()
            ->shouldBeCalled()
            ->willReturn('GET');

        $request->getUri()
            ->shouldBeCalled()
            ->willReturn($uri);

        $uri->getPath()
            ->shouldBeCalled()
            ->willReturn('/phpspec/test');

        $this->registry->getBestMatch('get', '/phpspec/test')
            ->shouldBeCalled()
            ->willReturn($registryEntry);

        $request->withAttribute('_phpspec_request-handler', $registryEntry)
            ->shouldBeCalled()
            ->willReturn($request);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $response->withHeader('X-Route-Resolver', 'router-registry')
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->setAttributeName('_phpspec_request-handler')
            ->process($request, $handler)
            ->shouldReturn($response);
    }
}
