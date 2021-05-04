<?php
namespace Minds\Core\Notifications\PostSubscriptions;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Post Subscriptions Routes
 * @package Minds\Core\Notifications\PostSubscriptions
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/notifications/follow')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddlware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            '',
                            Ref::_('Notifications\PostSubscriptions\Controller', 'get')
                        );
                        $route->put(
                            '',
                            Ref::_('Notifications\PostSubscriptions\Controller', 'put')
                        );
                        $route->delete(
                            '',
                            Ref::_('Notifications\PostSubscriptions\Controller', 'delete')
                        );
                    });
            });
    }
}
