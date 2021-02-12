<?php
namespace Minds\Core\Blockchain\Metrics;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * Blockchain Metrics Routes
 * @package Minds\Core\Blockchain\Metrics
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/blockchain/metrics')
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Blockchain\Metrics\Controller', 'get')
                );
            });
    }
}
