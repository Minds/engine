<?php

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/multi-tenant/configs')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'image/:imageType',
                    Ref::_(Image\Controller::class, 'get')
                );
                $route->post(
                    'image/upload',
                    Ref::_(Image\Controller::class, 'upload')
                );
            });
    }
}
