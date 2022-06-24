<?php

namespace Minds\Core\FeedNotices;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Routes for FeedNotices.
 */
class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes.
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/feed-notices')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('FeedNotices\Controller', 'getNotices')
                );
            });
    }
}
