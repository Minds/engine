<?php
namespace Minds\Core\Channels\Groups;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Channel Groups Routes
 * @package Minds\Core\Channels\Groups
 */
class Routes extends ModuleRoutes
{
    /**
     * Register Routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/channel/:guid/groups')
            ->withMiddleware([])
            ->do(function (Route $route) {
                // List
                $route->get('/', Ref::_('Channels\Groups\Controller', 'getList'));

                // Count
                $route->get('/count', Ref::_('Channels\Groups\Controller', 'count'));
            });
    }
}
