<?php
namespace Minds\Core\Rewards;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * Rewards Routes
 * @package Minds\Core\Rewards
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        // Logged in endpoints
        $this->route
            ->withPrefix('api/v3/rewards')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Rewards\Controller', 'get')
                );
                $route->get(
                    'withdrawals',
                    Ref::_('Rewards\Controller', 'getWithdrawals')
                );
            });
        
        // Admin endpoints
        $this->route
            ->withPrefix('api/v3/rewards/admin')
            ->withMiddleware([
                AdminMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->post(
                    'missing',
                    Ref::_('Rewards\Withdraw\Admin\Controller', 'addMissingWithdrawal')
                );
                $route->post(
                    'confirm',
                    Ref::_('Rewards\Withdraw\Admin\Controller', 'forceConfirmation')
                );
                $route->post(
                    'redispatch',
                    Ref::_('Rewards\Withdraw\Admin\Controller', 'redispatchCompleted')
                );
                $route->post(
                    'gc',
                    Ref::_('Rewards\Withdraw\Admin\Controller', 'runGarbageCollection')
                );
                $route->post(
                    'gc-single',
                    Ref::_('Rewards\Withdraw\Admin\Controller', 'runGarbageCollectionSingle')
                );
            });
    }
}
