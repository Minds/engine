<?php
declare(strict_types=1);

namespace Minds\Core\Payments\Lago;

use Minds\Core\Di\Ref;
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
            ->withPrefix('api/v3/payments/lago')
            ->do(function (Route $route): void {
                $route->post(
                    'webhook',
                    Ref::_(WebhookController::class, 'handleWebhook')
                );
            });
    }
}
