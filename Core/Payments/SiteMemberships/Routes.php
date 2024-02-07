<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships;

use Minds\Core\Di\Ref;
use Minds\Core\Payments\SiteMemberships\Controllers\SiteMembershipSubscriptionsPsrController;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route->withPrefix('api/v3/payments/site-memberships')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route): void {
                $route->get(
                    ':membershipGuid/checkout',
                    Ref::_(SiteMembershipSubscriptionsPsrController::class, 'goToSiteMembershipCheckoutLink')
                );
                $route->get(
                    ':membershipGuid/checkout/complete',
                    Ref::_(SiteMembershipSubscriptionsPsrController::class, 'completeSiteMembershipPurchase')
                );
            });
    }
}
