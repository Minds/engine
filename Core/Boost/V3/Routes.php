<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/boosts')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route): void {
                $route->get(
                    'feed',
                    Ref::_(Controller::class, 'getBoostFeed')
                );
                $route->get(
                    '',
                    Ref::_(Controller::class, 'getOwnBoosts')
                );

                $route->post(
                    '',
                    Ref::_(Controller::class, 'createBoost')
                );

                $route
                    ->withMiddleware([
                        AdminMiddleware::class
                    ])
                    ->do(function (Route $route): void {
                        $route->get(
                            'admin',
                            Ref::_(Controller::class, 'getBoostsForAdmin')
                        );
                        $route->post(
                            ':guid/approve',
                            Ref::_(Controller::class, 'approveBoost')
                        );
                        $route->post(
                            ':guid/reject',
                            Ref::_(Controller::class, 'rejectBoost')
                        );
                    });
            });
    }
}
