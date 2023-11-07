<?php

namespace Minds\Core\MultiTenant\AutoLogin;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
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
            ->withPrefix('api/v3/multi-tenant/auto-login')
            ->do(function (Route $route) {
                // logged-out routes.
                $route->post(
                    'generate',
                    Ref::_(Controller::class, 'generate')
                );

                $route->get(
                    'login',
                    Ref::_(Controller::class, 'login')
                );
            });
    }
}
