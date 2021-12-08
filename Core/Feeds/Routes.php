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
            ->do(function (Route $route) {
                $route->get(
                    'logged-out',
                    Ref::_('Feeds\Controller', 'getLoggedOutFeed')
                );

                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            '',
                            Ref::_('Feeds\Controller', 'getFeed')
                        );
                        $route->get(
                            'feed/unseen-top',
                            Ref::_('Feeds\UnseenTopFeed\Controller', 'getUnseenTopFeed')
                        );
                        $route->delete(
                            ':urn',
                            Ref::_('Feeds\Activity\Controller', 'delete')
                        );
                    });
            });
    }
}
