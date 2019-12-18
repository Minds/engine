<?php
/**
 * SessionMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Di\Di;
use Minds\Core\Session;
use Minds\Core\Sessions\Manager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware implements MiddlewareInterface
{
    /** @var Manager */
    protected $session;

    /** @var string */
    protected $attributeName = '_user';

    /**
     * SessionMiddleware constructor.
     * @param Manager $session
     */
    public function __construct(
        $session = null
    ) {
        $this->session = $session ?: Di::_()->get('Sessions\Manager');
    }

    /**
     * @param string $attributeName
     * @return SessionMiddleware
     */
    public function setAttributeName(string $attributeName): SessionMiddleware
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
        if (!$request->getAttribute($this->attributeName)) {
            $this->session
                ->withRouterRequest($request);

            return $handler->handle(
                $request
                    ->withAttribute($this->attributeName, Session::getLoggedinUser() ?: null)
            );
        }

        return $handler
            ->handle($request);
    }
}
