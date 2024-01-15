<?php declare(strict_types=1);

namespace Minds\Core\Router\Middleware;

use Minds\Core\ActivityPub\Services\FederationEnabledService;
use Minds\Core\Config\Config;
use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * This middleware will prevent an endpoint from being called if federation is disabled.
 */
class FederationEnabledMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?FederationEnabledService $federationEnabledService = null
    ) {
        $this->federationEnabledService ??= Di::_()->get(FederationEnabledService::class);
    }

    /**
     * Process an incoming server request, blocking access if federation is not enabled.
     * @param ServerRequestInterface $request - request.
     * @param RequestHandlerInterface $handler - handler.
     * @return ResponseInterface - response.
     * @throws ForbiddenException - if federation is not enabled.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->federationEnabledService->isEnabled()) {
            throw new ForbiddenException('Federation is disabled for this network.');
        }

        return $handler
            ->handle($request);
    }
}
