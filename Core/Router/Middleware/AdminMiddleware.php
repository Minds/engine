<?php declare(strict_types=1);
/**
 * AdminMiddleware
 * @author edgebal
 */

namespace Minds\Core\Router\Middleware;

use Minds\Core\Router\Enums\RequestAttributeEnum;
use Minds\Core\Router\Exceptions\ForbiddenException;
use Minds\Core\Security\XSRF;
use Minds\Entities\User;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AdminMiddleware implements MiddlewareInterface
{
    /** @var string */
    protected $attributeName = RequestAttributeEnum::USER;

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
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (
            !$request->getAttribute($this->attributeName)
        ) {
            throw new ForbiddenException();
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
