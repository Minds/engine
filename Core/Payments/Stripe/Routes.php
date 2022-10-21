<?php
namespace Minds\Core\Payments\Stripe;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Stripe Routes
 * @package Minds\Core\Payments\Stripe
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/payments/stripe')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->post(
                            'connect/account',
                            Ref::_('Stripe\Connect\Controller', 'createAccount')
                        );
                        $route->get(
                            'connect/account',
                            Ref::_('Stripe\Connect\Controller', 'getAccount')
                        );
                        $route->get(
                            'connect/onboarding',
                            Ref::_('Stripe\Connect\Controller', 'redirectToOnboarding')
                        );
                    });
            });
    }
}
