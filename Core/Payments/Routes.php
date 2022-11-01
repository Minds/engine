<?php

declare(strict_types=1);

namespace Minds\Core\Payments;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/payments')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Payments\Controller', 'getPayments')
                );
                $route->get(
                    'receipt/:paymentId',
                    Ref::_('Payments\Controller', 'redirectToReceipt')
                );
            });
    }
}
