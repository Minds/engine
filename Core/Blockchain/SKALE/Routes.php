<?php
namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/blockchain/skale')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'can-exit',
                    Ref::_('Blockchain\Skale\Controller', 'canExit')
                );
                // $route->get(
                //     'users',
                //     Ref::_('Blockchain\Skale\Controller', 'getAllUsers')
                // );
            });
    }
}
