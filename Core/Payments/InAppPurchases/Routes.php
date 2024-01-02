<?php
namespace Minds\Core\Payments\InAppPurchases;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * InAppPurchases
 * @package Minds\Core\Payments\InAppPurchases
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/payments/iap')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->post(
                            'subscription/acknowledge',
                            Ref::_(Controller::class, 'acknowledgeSubscription')
                        );
                    });

                $route
                    ->post(
                        'apple',
                        Ref::_(Controller::class, 'processIOSSubscriptionRenewals')
                    );
            });
    }
}
