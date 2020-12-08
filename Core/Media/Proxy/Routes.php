<?php
/**
 * Routes
 */

namespace Minds\Core\Media\Proxy;

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
            ->withPrefix('api/v3/media/proxy')
            ->do(function (Route $route) {
                $route->get(
                    '/',
                    Ref::_('Media\Proxy\Controller', 'proxy')
                );
            });
    }
}
