<?php
namespace Minds\Core\Onboarding;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Exceptions\UserErrorException;

/**
 * Suggestions Routes
 * @package Minds\Core\Onboarding
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/onboarding')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Onboarding\Controller', 'getProgress')
                );
                $route->put(
                    'seen',
                    Ref::_('Onboarding\Controller', 'setSeen')
                );
                $route->put(
                    'claim-reward',
                    Ref::_('Onboarding\Controller', 'claimReward')
                );
            });
    }
}
