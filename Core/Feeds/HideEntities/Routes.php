<?php

/**
 * Routes
 * @author Mark
 */

namespace Minds\Core\Feeds\HideEntities;

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
            ->withPrefix('api/v3/newsfeed/hide-entities')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->put(
                    ':entityGuid',
                    Ref::_(Controller::class, 'hideEntity')
                );
            });
    }
}
