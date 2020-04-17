<?php
/**
 * Routes
 * @author mark
 */

namespace Minds\Core\Monetization\Partners;

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
            ->withPrefix('api/v3/monetization/partners')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'balance',
                    Ref::_('Monetization\Partners\Controllers', 'getBalance')
                );
            });
    }
}
