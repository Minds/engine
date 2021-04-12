<?php
namespace Minds\Core\Channel;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Channel
 * @package Minds\Core\Channel
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/channel')
            ->do(function (Route $route) {
                $route->get(
                    ':channel',
                    Ref::_('Channel\Controller', 'get')
                );
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->post(
                            ':update',
                            Ref::_('Channel\Controller', 'update')
                        );
                        $route->delete(
                            ':delete',
                            Ref::_('Channel\Controller', 'delete')
                        );
                    });
            });
    }
}
