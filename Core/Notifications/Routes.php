<?php
namespace Minds\Core\Notifications;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Notifications Routes
 * @package Minds\Core\Notifications
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/notifications')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddlware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'count',
                            Ref::_('Notifications\Controller', 'getCount')
                        );
                        $route->get(
                            'single',
                            Ref::_('Notifications\Controller', 'getSingle')
                        );
                        $route->get(
                            'settings',
                            Ref::_('Notifications\Controller', 'getSettings')
                        );
                        $route->get(
                            'list',
                            Ref::_('Notifications\Controller', 'getList')
                        );
                        $route->post(
                            'settings',
                            Ref::_('Notifications\Controller', 'updateSettings')
                        );
                        $route->post(
                            'token',
                            Ref::_('Notifications\Controller', 'updateToken')
                        );
                        $route->post(
                            'test',
                            Ref::_('Notifications\Controller', 'test')
                        );
                    });
            });
    }
}
