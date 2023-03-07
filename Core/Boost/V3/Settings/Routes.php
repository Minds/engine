<?php

declare(strict_types=1);

namespace Minds\Core\Boost\V3\Settings;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/boosts/settings')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Boost\V3\Settings\Controller', 'getSettings')
                );
                $route->post(
                    '',
                    Ref::_('Boost\V3\Settings\Controller', 'storeSettings')
                );
            });
    }
}
