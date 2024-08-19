<?php

namespace Minds\Core\MultiTenant\Billing;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/multi-tenant/billing')
            ->do(function (Route $route) {
                // logged-out routes.
                $route->get(
                    'external-checkout',
                    Ref::_(BillingPsrController::class, 'externalCheckout')
                );
                $route->get(
                    'external-callback',
                    Ref::_(BillingPsrController::class, 'externalCallback')
                );
            });
    }
}
