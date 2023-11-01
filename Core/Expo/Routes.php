<?php
declare(strict_types=1);

namespace Minds\Core\Expo;

use Minds\Core\Di\Ref;
use Minds\Core\Expo\Controllers\AndroidController;
use Minds\Core\Expo\Controllers\iOSController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/expo')
            // TODO: refine
            ->withMiddleware([
                // LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->post(
                    'credentials/android',
                    Ref::_(AndroidController::class, 'setProjectCredentials')
                );
                $route->delete(
                    'credentials/android/:appCredentialsId',
                    Ref::_(AndroidController::class, 'deleteProjectCredentials')
                );
                $route->post(
                    'credentials/ios',
                    Ref::_(iOSController::class, 'setProjectCredentials')
                );
                $route->delete(
                    'credentials/ios/:appCredentialsId',
                    Ref::_(iOSController::class, 'deleteProjectCredentials')
                );
            });
    }
}
