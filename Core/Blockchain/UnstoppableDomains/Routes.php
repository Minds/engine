<?php
/**
 * Routes
 * @author Mark
 */

namespace Minds\Core\Blockchain\UnstoppableDomains;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
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
            ->withPrefix('api/v3/blockchain/unstoppable-domains')
            ->do(function (Route $route) {
                $route->get(
                    'reverse/:walletAddress',
                    Ref::_('Blockchain\UnstoppableDomains\Controller', 'getDomains')
                );
            });
    }
}
