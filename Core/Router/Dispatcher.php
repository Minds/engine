<?php declare(strict_types=1);
/**
 * Dispatcher
 * @author edgebal
 */

namespace Minds\Core\Router;

use Minds\Core\Router\Middleware\Kernel\EmptyResponseMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Dispatcher implements RequestHandlerInterface
{
    /** @var MiddlewareInterface */
    protected $emptyResponseMiddleware;

    /**
     * Dispatcher constructor.
     * @param MiddlewareInterface $emptyResponseMiddleware
     */
    public function __construct(
        $emptyResponseMiddleware = null
    ) {
        $this->emptyResponseMiddleware = $emptyResponseMiddleware ?: new EmptyResponseMiddleware();
    }

    /** @var MiddlewareInterface[] */
    protected $middleware = [];

    /**
     * @param MiddlewareInterface $middleware
     * @return $this
     */
    public function pipe(MiddlewareInterface $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        if (count($this->middleware) === 0) {
            return $this->emptyResponseMiddleware->process($request, $this);
        }

        $middleware = array_shift($this->middleware);
        return $middleware->process($request, $this);
    }
}
