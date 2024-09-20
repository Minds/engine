<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Bootstrap;

use Minds\Core\Di\Ref;
use Minds\Core\MultiTenant\Bootstrap\Controllers\BootstrapProgressPsrController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/tenant-bootstrap')
            ->do(function (Route $route): void {
                $route->get(
                    'progress',
                    Ref::_(BootstrapProgressPsrController::class, 'getProgress')
                );
            });
    }
}
