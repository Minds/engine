<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/twitter')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->post(
                    'request-oauth-token',
                    Ref::_('Supermind\Controller', 'requestTwitterOAuthToken')
                );
            });
    }
}
