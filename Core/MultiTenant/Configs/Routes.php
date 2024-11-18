<?php

namespace Minds\Core\MultiTenant\Configs;

use Minds\Core\Di\Ref;
use Minds\Core\MultiTenant\Configs\Controllers\CustomScriptPsrController;
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
            ->withPrefix('api/v3/multi-tenant/configs')
            ->do(function (Route $route) {
                // logged-out routes.
                $route->get(
                    'image/:imageType',
                    Ref::_(Image\Controller::class, 'get')
                );

                // admin routes.
                $route
                    ->withMiddleware([
                        AdminMiddleware::class,
                    ])
                    ->do(function (Route $route): void {
                        $route->post(
                            'image/upload',
                            Ref::_(Image\Controller::class, 'upload')
                        );

                        $route->put(
                            'custom-script',
                            Ref::_(CustomScriptPsrController::class, 'update')
                        );
                    });
            });
    }
}
