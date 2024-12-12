<?php
namespace Minds\Core\Chat;

use Minds\Core\Chat\Controllers\ChatImagePsrController;
use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\Middleware\PermissionsMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;

/**
 * Routes
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('fs/v3/chat/image')
            ->do(function (Route $route) {
                $route->get(
                    ':roomGuid/:messageGuid',
                    Ref::_(ChatImagePsrController::class, 'get')
                );
            });

        $this->route
            ->withPrefix('api/v3/chat/image')
            ->withMiddleware([
                LoggedInMiddleware::class,
                [
                    'class' => PermissionsMiddleware::class,
                    'args' => [ PermissionsEnum::CAN_UPLOAD_CHAT_MEDIA ]
                ]
            ])
            ->do(function (Route $route) {
                $route->post(
                    'upload/:roomGuid',
                    Ref::_(ChatImagePsrController::class, 'upload')
                );
            });
    }
}
