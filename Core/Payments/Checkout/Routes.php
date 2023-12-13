<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Checkout;

use Minds\Core\Di\Ref;
use Minds\Core\Payments\Checkout\Controllers\CheckoutPsrController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route->withPrefix('api/v3/payments/checkout')
            ->do(function (Route $route) {
                $route
                    ->get(
                        route: 'complete',
                        binding: Ref::_(CheckoutPsrController::class, 'completeCheckout')
                    );
            });
    }
}
