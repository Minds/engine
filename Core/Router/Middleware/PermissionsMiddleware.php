<?php
declare(strict_types=1);

namespace Minds\Core\Router\Middleware;

use Minds\Core\Di\Di;
use Minds\Core\Router\Exceptions\UnauthorizedException;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;
use Minds\Core\Security\Rbac\Exceptions\RbacNotAllowed;
use Minds\Core\Security\Rbac\Services\RolesService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to check if user has permission to access a route.
 *
 * Example usage:
 *
 * ```
 * $route->withMiddleware([[
 *     'class' => PermissionMiddleware::class,
 *     'args' => [ PermissionsEnum::CAN_MODERATE_CONTENT ]
 * ]])
 * ```
 */
class PermissionsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private PermissionsEnum $permission,
        private ?RolesService $rolesService = null
    ) {
        $this->rolesService ??= Di::_()->get(RolesService::class);
    }

    /**
     * Process a request.
     * @param ServerRequestInterface $request - the request.
     * @param RequestHandlerInterface $handler - the request handler.
     * @return ResponseInterface - the response.
     * @throws RbacNotAllowed - if the user does not have the required permission.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $loggedInUser = $request->getAttribute('_user');

        if (!$loggedInUser) {
            throw new UnauthorizedException('User not logged in.');
        }

        if (!$this->rolesService->hasPermission($loggedInUser, $this->permission)) {
            throw new RbacNotAllowed($this->permission);
        }

        return $handler->handle($request);
    }
}
