<?php
declare(strict_types=1);

namespace Minds\Core\Admin;

use Minds\Core\Admin\Controllers\UsersPsrController;
use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withMiddleware([
                AdminMiddleware::class,
            ])
            ->withPrefix('api/v3/admin')
            ->do(function (Route $route) {
                $route->do(function (Route $route) {
                    $route->get(
                        'users',
                        Ref::_(UsersPsrController::class, 'getUsers')
                    );
                });
            });
    }
}
