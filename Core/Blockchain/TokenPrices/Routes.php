<?php
namespace Minds\Core\Blockchain\TokenPrices;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * TokenPrices Routes
 * @package Minds\Core\Blockchain\TokenPrices
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/blockchain/token-prices')
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Blockchain\TokenPrices\Controller', 'get')
                );
            });
    }
}
