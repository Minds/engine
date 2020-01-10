<?php declare(strict_types=1);
/**
 * RouteResolverMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Di\Di;
use Minds\Core\Router\PrePsr7;
use Minds\Core\Router\Registry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RouteResolverMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected $attributeName = '_request-handler';

    /** @var Registry */
    protected $registry;

    /**
     * RouteResolverMiddleware constructor.
     * @param Registry $registry
     */
    public function __construct(
        $registry = null
    ) {
        $this->registry = $registry ?: Di::_()->get('Router\Registry');
    }

    /**
     * @param string $attributeName
     * @return RouteResolverMiddleware
     */
    public function setAttributeName(string $attributeName): RouteResolverMiddleware
    {
        $this->attributeName = $attributeName;
        return $this;
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
        // Module Router

        $registryEntry = $this->registry->getBestMatch(
            strtolower($request->getMethod()),
            $request->getUri()->getPath()
        );

        if ($registryEntry) {
            return $handler
                ->handle(
                    $request
                        ->withAttribute($this->attributeName, $registryEntry)
                )
                ->withHeader('X-Route-Resolver', 'router-registry');
        }

        // Pre PSR-7 Fallback Handlers

        $prePsr7Fallback = new PrePsr7\Fallback();

        // Pre PSR-7 Controllers

        if ($prePsr7Fallback->shouldRoute($request->getUri()->getPath())) {
            return $prePsr7Fallback
                ->handle($request)
                ->withHeader('X-Route-Resolver', 'pre-psr7');
        }

        // Static HTML

        if ($request->getAttribute('accept') === 'html') {
            return $prePsr7Fallback
                ->handleStatic($request)
                ->withHeader('X-Route-Resolver', 'pre-psr7-static');
        }

        // No route handler

        return $handler
            ->handle($request);
    }
}
