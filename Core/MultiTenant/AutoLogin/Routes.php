<?php

namespace Minds\Core\MultiTenant\AutoLogin;

use Minds\Core\Di\Ref;
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
                $route->get(
                    'login-url',
                    Ref::_(Controller::class, 'getLoginUrl')
                );

                $route->post(
                    'login',
                    Ref::_(Controller::class, 'login')
                );
            });
    }
}
