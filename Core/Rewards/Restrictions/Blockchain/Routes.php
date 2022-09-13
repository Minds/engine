<?php

namespace Minds\Core\Rewards\Restrictions\Blockchain;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Route;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/rewards/check')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    ':address',
                    Ref::_('Rewards\Restrictions\Blockchain\Controller', 'check')
                );
            });
    }
}
