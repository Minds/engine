<?php
namespace Minds\Core\Boost;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * Boost Routes
 * @package Minds\Core\Boost
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/boost')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'liquidity-spot',
                    Ref::_('Boost\LiquiditySpot\Controller', 'get')
                );
            });
    }
}
