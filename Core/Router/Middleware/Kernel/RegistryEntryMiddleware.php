<?php declare(strict_types=1);
/**
 * RegistryEntryMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Exception;
use Minds\Core\Di\Di;
use Minds\Core\Di\Ref as DiRef;
use Minds\Core\Router\RegistryEntry;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RegistryEntryMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected $attributeName = '_router-registry-entry';

    /**
     * @param string $attributeName
     * @return RegistryEntryMiddleware
     */
    public function setAttributeName(string $attributeName): RegistryEntryMiddleware
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
        /** @var RegistryEntry $registryEntry */
        $registryEntry = $request->getAttribute($this->attributeName);

        if ($registryEntry) {
            $binding = $registryEntry->getBinding();
            $parameters = $registryEntry->extract($request->getUri()->getPath());

            if ($binding instanceof DiRef) {
                return call_user_func(
                    [
                        Di::_()->get($binding->getProvider()),
                        $binding->getMethod()
                    ],
                    $request
                        ->withAttribute('parameters', $parameters)
                );
            } elseif (is_callable($binding)) {
                return call_user_func(
                    $binding,
                    $request
                        ->withAttribute('parameters', $parameters)
                );
            } else {
                throw new Exception("Invalid router binding");
            }
        }

        return $handler
            ->handle($request);
    }
}
