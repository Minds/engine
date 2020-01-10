<?php
/**
 * OauthMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Session;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zend\Diactoros\Response;

class OauthMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected $attributeName = '_user';

    /**
     * @param string $attributeName
     * @return OauthMiddleware
     */
    public function setAttributeName(string $attributeName): OauthMiddleware
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
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$request->getAttribute($this->attributeName)) {
            Session::withRouterRequest($request, new Response());

            return $handler->handle(
                $request
                    ->withAttribute($this->attributeName, Session::getLoggedinUser() ?: null)
            );
        }

        return $handler
            ->handle($request);
    }
}
