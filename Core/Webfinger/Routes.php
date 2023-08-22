<?php

declare(strict_types=1);

namespace Minds\Core\Webfinger;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('.well-known/webfinger')
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_(Controller::class, 'get')
                );
            });
    }
}
