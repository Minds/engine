<?php

namespace Minds\Core\Metrics;

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
            ->withPrefix('metrics')
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Metrics\Controller', 'getMetrics')
                );
            });
    }
}
