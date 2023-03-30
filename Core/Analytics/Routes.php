<?php
declare(strict_types=1);

namespace Minds\Core\Analytics;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/analytics')
            ->do(function (Route $route) {
                $route->do(function (Route $route) {
                    $route->post(
                        'click/:entityGuid',
                        Ref::_(Controller::class, 'trackClick')
                    );
                });
            });
    }
}
