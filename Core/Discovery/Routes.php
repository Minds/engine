<?php
/**
 * Routes
 * @author edgebal
 */

namespace Minds\Core\Discovery;

use Minds\Core\Di\Ref;
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
            ->withPrefix('api/v3/discovery')
            ->do(function (Route $route) {
                $route->get(
                    'trends',
                    Ref::_('Discovery\Controllers', 'getTrends')
                );
                $route->get(
                    'search',
                    Ref::_('Discovery\Controllers', 'getSearch')
                );
                $route->get(
                    'tags',
                    Ref::_('Discovery\Controllers', 'getTags')
                );
                $route->post(
                    'tags',
                    Ref::_('Discovery\Controllers', 'setTags')
                );
                $route->get(
                    'for-you',
                    Ref::_('Discovery\Controllers', 'getForYou')
                );
            });
    }
}
