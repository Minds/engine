<?php
declare(strict_types=1);

namespace Minds\Core\Payments\SiteMemberships\Webhooks;

use Minds\Core\Di\Ref;
use Minds\Core\Payments\SiteMemberships\Webhooks\Controllers\SiteMembershipWebhooksPsrController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('/api/v3/stripe/webhooks')
            ->do(function (Route $route): void {
                $route->withPrefix('site-memberships')
                    ->do(function (Route $route): void {
                        $route->post(
                            '/process-renewal',
                            Ref::_(
                                SiteMembershipWebhooksPsrController::class,
                                'processSubscriptionRenewal'
                            )
                        );
                    });
            });
    }
}
