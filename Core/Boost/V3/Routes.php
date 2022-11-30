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
                    '/feed',
                    Ref::_('Boost\V3\Controller', 'getBoostFeed')
                );
                $route->get(
                    '',
                    Ref::_('Boost\V3\Controller', 'getOwnBoosts')
                );

                $route->post(
                    '',
                    Ref::_('Boost\V3\Controller', 'createBoost')
                );

                $route
                    ->withMiddleware([
                        AdminMiddleware::class
                    ])
                    ->do(function (Route $route): void {
                        $route->get(
                            'pending',
                            Ref::_('Boost\V3\Controller', 'getAdminPendingBoosts')
                        );
                        $route->post(
                            ':guid/approve',
                            Ref::_('Boost\V3\Controller', 'approveBoost')
                        );
                        $route->post(
                            ':guid/reject',
                            Ref::_('Boost\V3\Controller', 'rejectBoost')
                        );
                    });
            });
    }
}
