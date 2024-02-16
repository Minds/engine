<?php
declare(strict_types=1);

namespace Minds\Core\Notifications\Push\ManualSend;

use Minds\Core\Di\Ref;
use Minds\Core\Notifications\Push\ManualSend\Controllers\ManualSendController;
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
            ->withPrefix('api/v3/notifications/push/manual-send')
            ->withMiddleware([
                // LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->post(
                    '',
                    Ref::_(ManualSendController::class, 'send')
                );
            });
    }
}
