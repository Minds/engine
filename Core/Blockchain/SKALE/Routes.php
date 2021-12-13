<?php
namespace Minds\Core\Blockchain\SKALE;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * SKALE Routes.
 * @package Minds\Core\Blockchain\SKALE
 */
class Routes extends ModuleRoutes
{
    /**
     * Register routes.
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
                $route->post(
                    'faucet',
                    Ref::_('Blockchain\SKALE\Controller', 'requestFromFaucet')
                );
            });
    }
}
