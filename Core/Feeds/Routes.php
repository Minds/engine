<?php
/**
 * Routes
 * @author edgebal
 */

namespace Minds\Core\Feeds;

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
            ->withPrefix('api/v3/newsfeed')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->post(
                    '',
                    Ref::_('Feeds\Activity\Manager', 'add')
                );

                $route->post(
                    ':guid',
                    Ref::_('Feeds\Activity\Manager', 'update')
                );

                $route->delete(
                    ':guid',
                    Ref::_('Feeds\Activity\Manager', 'delete')
                );
            });
    }
}
