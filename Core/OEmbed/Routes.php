<?php
/**
 * Routes
 */

namespace Minds\Core\OEmbed;

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
            ->withPrefix("api/v3/oembed")
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('OEmbed\Controller', 'getOEmbed')
                );
            });
    }
}
