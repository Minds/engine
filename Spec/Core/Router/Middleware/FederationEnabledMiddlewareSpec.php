<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Router\Middleware;

use Minds\Core\ActivityPub\Services\FederationEnabledService;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Middleware\FederationEnabledMiddleware;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FederationEnabledMiddlewareSpec extends ObjectBehavior
{
    /** @var FederationEnabledService */
    private Collaborator $federationEnabledService;

    public function let(
        FederationEnabledService $federationEnabledService
    ) {
        $this->federationEnabledService = $federationEnabledService;
        $this->beConstructedWith($federationEnabledService);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(FederationEnabledMiddleware::class);
    }

    public function it_should_process_when_federation_is_enabled(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler,
        ResponseInterface $response
    ) {
        $this->federationEnabledService->isEnabled()
            ->shouldBeCalled()
            ->willReturn(true);

        $handler->handle($request)
            ->shouldBeCalled()
            ->willReturn($response);

        $this
            ->process($request, $handler)
            ->shouldReturn($response);
    }

    public function it_should_NOT_process_when_federation_is_disabled(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ) {
        $this->federationEnabledService->isEnabled()
            ->shouldBeCalled()
            ->willReturn(false);

        $handler->handle($request)
            ->shouldNotBeCalled();

        $this->shouldThrow(ForbiddenException::class)->duringProcess($request, $handler);
    }
}
