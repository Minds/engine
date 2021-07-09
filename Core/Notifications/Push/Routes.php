<?php
namespace Minds\Core\Notifications\Push;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Notifications Routes
 * @package Minds\Core\Notifications\Push
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/notifications/push')
            ->withMiddlware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->post(
                    'token',
                    Ref::_('Notifications\Push\DeviceSubscriptions\Controller', 'registerToken')
                );
                $route->delete(
                    'token/:token',
                    Ref::_('Notifications\Push\DeviceSubscriptions\Controller', 'deleteToken')
                );
                //
                $route->get(
                    'settings',
                    Ref::_('Notifications\Push\Settings\Controller', 'getSettings')
                );
                $route->post(
                    'settings/:notificationGroup',
                    Ref::_('Notifications\Push\Settings\Controller', 'toggle')
                );
            });
    }
}
