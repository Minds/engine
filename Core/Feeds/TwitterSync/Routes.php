<?php
/**
 * Routes
 * @author mark
 */

namespace Minds\Core\Feeds\TwitterSync;

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
            ->withPrefix('api/v3/twitter-sync')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Feeds\TwitterSync\Controller', 'getConnectedAccount')
                );
                $route->post(
                    '',
                    Ref::_('Feeds\TwitterSync\Controller', 'updateAccount')
                );
                $route->post(
                    'connect',
                    Ref::_('Feeds\TwitterSync\Controller', 'connectAccount')
                );
                $route->delete(
                    '',
                    Ref::_('Feeds\TwitterSync\Controller', 'disconnectAccount')
                );
            });
    }
}
