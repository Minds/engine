<?php
namespace Minds\Core\Notifications\UpdateMarkers;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Update markers Routes
 * @package Minds\Core\Notifications\UpdateMarkers
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/notifications/markers')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddlware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            '',
                            Ref::_('Notifications\UpdateMarkers\Controller', 'getList')
                        );
                        $route->post(
                            'read',
                            Ref::_('Notifications\UpdateMarkers\Controller', 'readMarker')
                        );
                        $route->put(
                            'heartbeat',
                            Ref::_('Notifications\UpdateMarkers\Controller', 'markGathering')
                        );
                    });
            });
    }
}
