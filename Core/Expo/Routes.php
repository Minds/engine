<?php
declare(strict_types=1);

namespace Minds\Core\Expo;

use Minds\Core\Di\Ref;
use Minds\Core\Expo\Controllers\AndroidCredentialsController;
use Minds\Core\Expo\Controllers\iOSCredentialsController;
use Minds\Core\Router\Middleware\AdminMiddleware;
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
            ->withMiddleware([
                // AdminMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->post(
                    'credentials/android',
                    Ref::_(AndroidCredentialsController::class, 'setProjectCredentials')
                );
                $route->delete(
                    'credentials/android/:appCredentialsId',
                    Ref::_(AndroidCredentialsController::class, 'deleteProjectCredentials')
                );
                $route->post(
                    'credentials/ios',
                    Ref::_(iOSCredentialsController::class, 'setProjectCredentials')
                );
                $route->delete(
                    'credentials/ios/:appCredentialsId',
                    Ref::_(iOSCredentialsController::class, 'deleteProjectCredentials')
                );
            });
    }
}
