<?php
/**
 * Routes
 * @author mark
 */

namespace Minds\Core\Monetization\EarningsOverview;

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
            ->withPrefix('api/v3/monetization/earnings')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'overview',
                    Ref::_('Monetization\EarningsOverview\Controllers', 'getOverview')
                );
            });
    }
}
