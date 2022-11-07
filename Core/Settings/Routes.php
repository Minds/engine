<?php

declare(strict_types=1);

namespace Minds\Core\Settings;

use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/supermind')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route): void {
                // TODO: add routes
            });
    }
}
