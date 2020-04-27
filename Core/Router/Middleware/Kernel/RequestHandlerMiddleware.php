<?php declare(strict_types=1);
/**
 * RequestHandlerMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Exception;
use Minds\Core\Router\Dispatcher;
use Minds\Core\Router\RegistryEntry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected $attributeName = '_request-handler';

    /**
     * @param string $attributeName
     * @return RequestHandlerMiddleware
     */
    public function setAttributeName(string $attributeName): RequestHandlerMiddleware
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
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestHandler = $request->getAttribute($this->attributeName);

        if ($requestHandler) {
            if ($requestHandler instanceof RegistryEntry) {
                // Setup sub-router

                $dispatcher = new Dispatcher();

                // Pipe route-specific middleware

                foreach ($requestHandler->getMiddleware() as $middleware) {
                    if (is_string($middleware)) {
                        if (!class_exists($middleware)) {
                            throw new Exception("{$middleware} does not exist");
                        }

                        $middlewareInstance = new $middleware;
                    } else {
                        $middlewareInstance = $middleware;
                    }

                    if (!($middlewareInstance instanceof MiddlewareInterface)) {
                        throw new Exception("{$middleware} is not a middleware");
                    }

                    $dispatcher->pipe($middlewareInstance);
                }

                // Dispatch with middleware

                return $dispatcher
                    ->pipe(
                        (new RegistryEntryMiddleware())
                            ->setAttributeName('_router-registry-entry')
                    )
                    ->handle(
                        $request
                            ->withAttribute('_router-registry-entry', $requestHandler)
                    );
            } elseif (is_callable($requestHandler)) {
                $response = call_user_func($requestHandler, $request);

                if ($response) {
                    return $response;
                }
            }
        }

        return $handler
            ->handle($request);
    }
}
