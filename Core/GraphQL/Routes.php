<?php
declare(strict_types=1);

namespace Minds\Core\GraphQL;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/graphql')
            ->do(function (Route $route): void {
                $route->post(
                    '',
                    Ref::_(Controller::class, 'exec')
                );
            });
    }
}
