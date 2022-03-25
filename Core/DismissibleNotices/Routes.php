<?php
/**
 * Routes
 */
namespace Minds\Core\DismissibleNotices;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * DismissibleNotice Routes
 */
class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/dismissible-notices')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->put(
                    ':id',
                    Ref::_('DismissibleNotices\Controller', 'dismissNotice')
                );
            });
    }
}
