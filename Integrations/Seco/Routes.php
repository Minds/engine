<?php
declare(strict_types=1);

namespace Minds\Integrations\Seco;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Integrations\Seco\Controllers\AIProxyContoller;
use Minds\Integrations\Seco\Controllers\ImportThreadsController;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->withPrefix('api/v3/seco')
            ->do(function (Route $route) {
                $route->post(
                    'import-threads/:groupGuid',
                    Ref::_(ImportThreadsController::class, 'importThreads')
                );

                $route->post(
                    'ai/chat',
                    Ref::_(AIProxyContoller::class, 'chat')
                );
            });
    }
}
