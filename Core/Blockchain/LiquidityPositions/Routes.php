<?php
namespace Minds\Core\Blockchain\LiquidityPositions;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * LiquidityPositions Routes
 * @package Minds\Core\Blockchain\LiquidityPositions
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/blockchain/liquidity-positions')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Blockchain\LiquidityPositions\Controller', 'get')
                );
            });
    }
}
