<?php
/**
 * Routes
 * @author Mark
 */

namespace Minds\Core\DismissibleWidgets;

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
            ->withPrefix('api/v3/dismissible-widgets')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->put(
                    ':id',
                    Ref::_('DismissibleWidgets\Controllers', 'putWidget')
                );
            });
    }
}
