<?php declare(strict_types=1);
/**
 * AdminMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware;

use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\XSRF;
use Minds\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected $attributeName = '_user';

    /** @var callable */
    private $xsrfValidateRequest;

    public function __construct(
        $xsrfValidateRequest = null
    ) {
        $this->xsrfValidateRequest = $xsrfValidateRequest ?: [XSRF::class, 'validateRequest'];
    }

    /**
     * @param string $attributeName
     * @return AdminMiddleware
     */
    public function setAttributeName(string $attributeName): AdminMiddleware
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
     * @throws ForbiddenException
     * @throws UnauthorizedException
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            !$request->getAttribute($this->attributeName) ||
            !call_user_func($this->xsrfValidateRequest)
        ) {
            throw new UnauthorizedException();
        }

        /** @var User $currentUser */
        $currentUser = $request->getAttribute($this->attributeName);

        if (!$currentUser->isAdmin()) {
            throw new ForbiddenException();
        }

        return $handler
            ->handle($request);
    }
}
