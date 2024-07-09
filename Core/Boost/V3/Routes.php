<?php
declare(strict_types=1);

namespace Minds\Core\Boost\V3;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\Middleware\PermissionsMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Security\Rbac\Enums\PermissionsEnum;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/boosts')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route): void {
                $route->get(
                    'feed',
                    Ref::_(Controller::class, 'getBoostFeed')
                );
                $route->get(
                    ':boostGuid',
                    Ref::_(Controller::class, 'getSingleBoost')
                );
                $route->get(
                    '',
                    Ref::_(Controller::class, 'getOwnBoosts')
                );

                $route->post(
                    '',
                    Ref::_(Controller::class, 'createBoost')
                );
                $route->post(
                    ':guid/cancel',
                    Ref::_(Controller::class, 'cancelBoost')
                );

                $route->post(
                    'prepare-onchain/:entityGuid',
                    Ref::_(Controller::class, 'prepareOnchainBoost')
                );

                $route
                    ->withMiddleware([
                        [
                            'class' => PermissionsMiddleware::class,
                            'args' => [ PermissionsEnum::CAN_MODERATE_CONTENT ]
                        ]
                    ])
                    ->do(function (Route $route): void {
                        $route->get(
                            'admin',
                            Ref::_(Controller::class, 'getBoostsForAdmin')
                        );
                        $route->get(
                            'admin/stats',
                            Ref::_(Controller::class, 'getAdminStats')
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
