<?php

declare(strict_types=1);

namespace Minds\Core\Supermind\Settings;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\Middleware\NotMultiTenantMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/supermind/settings')
            ->withMiddleware([
                LoggedInMiddleware::class,
                NotMultiTenantMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Supermind\Settings\Controller', 'getSettings')
                );
                $route->post(
                    '',
                    Ref::_('Supermind\Settings\Controller', 'storeSettings')
                );
            });
    }
}
