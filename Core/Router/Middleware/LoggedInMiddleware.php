<?php declare(strict_types=1);
/**
 * LoggedInMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware;

use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\XSRF;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LoggedInMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected string $attributeName = '_user';

    public function __construct()
    {
    }

    /**
     * @param string $attributeName
     * @return LoggedInMiddleware
     */
    public function setAttributeName(string $attributeName): LoggedInMiddleware
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
     * @throws UnauthorizedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $xsrf = new XSRF($request);
        if (
            !$request->getAttribute($this->attributeName) ||
            (!$xsrf->validateRequest() && !$request->getAttribute('oauth_user_id'))
        ) {
            throw new UnauthorizedException();
        }

        return $handler
            ->handle($request);
    }
}
