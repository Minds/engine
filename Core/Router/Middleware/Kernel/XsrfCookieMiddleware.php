<?php
/**
 * XsrfCookieMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware\Kernel;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\XSRF;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class XsrfCookieMiddleware implements MiddlewareInterface
{
    /** @var callable */
    private $xsrfValidateRequest;

    /** @var callable */
    private $xsrfSetCookie;

    public function __construct(
        $xsrfValidateRequest = null,
        $xsrfSetCookie = null
    ) {
        $this->xsrfValidateRequest = $xsrfValidateRequest ?: [XSRF::class, 'validateRequest'];
        $this->xsrfSetCookie = $xsrfSetCookie ?: [XSRF::class, 'setCookie'];
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
        // Set the XSRF cookie
        call_user_func($this->xsrfSetCookie);

        if (
            $request->getAttribute('_user') && // If logged in
            !$request->getAttribute('oauth_user_id') && // And not OAuth
            !call_user_func($this->xsrfValidateRequest, $request) // And xsrf validatio fails
        ) {
            throw new ForbiddenException();
        }

        return $handler
            ->handle($request);
    }
}
