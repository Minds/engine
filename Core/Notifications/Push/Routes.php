<?php
namespace Minds\Core\Notifications\Push;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

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
            ->withMiddleware([
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
            })
            ->withMiddleware([
                AdminMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->post(
                    'system',
                    Ref::_('Notifications\Push\System\Controller', 'schedule')
                );
                $route->get(
                    'system',
                    Ref::_('Notifications\Push\System\Controller', 'getHistory')
                );
            });
    }
}
