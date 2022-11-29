<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3\Summaries;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/boosts/:boostid/summary')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route): void {
                $route->get(
                    '',
                    Ref::_('Boosts\V3\Summaries\Controller', 'getBoostSummary')
                );
            });
    }
}
