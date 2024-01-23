<?php declare(strict_types=1);
/**
 * MultiTenantBootMiddleware
 * @author Mark Harding
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Di\Di;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MultiTenantBootMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ?MultiTenantBootService $bootService = null,
    ) {
        $this->bootService ??= Di::_()->get(MultiTenantBootService::class);

    }

    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Always reset configs
        $this->bootService->resetRootConfigs();

        // Process the request
        $this->bootService->bootFromRequest($request);

        return $handler
            ->handle($request);
    }
}
