<?php
namespace Minds\Core\Chat;

use Minds\Core\Chat\Controllers\ChatImagePsrController;
use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

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
            ->withMiddleware([LoggedInMiddleware::class])
            ->do(function (Route $route) {
                $route->post(
                    'upload/:roomGuid',
                    Ref::_(ChatImagePsrController::class, 'upload')
                );
            });
    }
}
