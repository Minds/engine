<?php
namespace Minds\Core\Blockchain\Wallets\OnChain\UniqueOnChain;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * UniqueOnChain Routes
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
            ->withPrefix('api/v3/blockchain/unique-onchain')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Blockchain\UniqueOnChain\Controller', 'get')
                );
                $route->post(
                    'validate',
                    Ref::_('Blockchain\UniqueOnChain\Controller', 'validate')
                );
                $route->delete(
                    'validate',
                    Ref::_('Blockchain\UniqueOnChain\Controller', 'unValidate')
                );
                $route
                    ->withMiddleware([
                        AdminMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'all',
                            Ref::_('Blockchain\UniqueOnChain\Controller', 'getAll')
                        );
                    });
            });
    }
}
