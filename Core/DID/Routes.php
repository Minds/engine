<?php
/**
 * Routes
 * @author Mark
 */

namespace Minds\Core\DID;

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
            ->withPrefix('/*')
            ->do(function (Route $route) {
                $route->get(
                    'did.json',
                    Ref::_('DID\Controller', 'getDIDDocument')
                );
            });
    }
}
