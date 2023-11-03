<?php
declare(strict_types=1);

namespace Minds\Core\Expo;

use Minds\Core\Di\Ref;
use Minds\Core\Expo\Controllers\AndroidCredentialsController;
use Minds\Core\Expo\Controllers\iOSCredentialsController;
use Minds\Core\Expo\Controllers\ProjectsController;
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
                AdminMiddleware::class
            ])
            ->do(function (Route $route) {
                // Android credentials
                $route->post(
                    'credentials/android',
                    Ref::_(AndroidCredentialsController::class, 'setProjectCredentials')
                );
                $route->put(
                    'credentials/android',
                    Ref::_(AndroidCredentialsController::class, 'updateProjectCredentials')
                );
                $route->delete(
                    'credentials/android',
                    Ref::_(AndroidCredentialsController::class, 'deleteProjectCredentials')
                );

                // iOS credentials
                $route->post(
                    'credentials/ios',
                    Ref::_(iOSCredentialsController::class, 'setProjectCredentials')
                );
                $route->put(
                    'credentials/ios',
                    Ref::_(iOSCredentialsController::class, 'updateProjectCredentials')
                );
                $route->delete(
                    'credentials/ios',
                    Ref::_(iOSCredentialsController::class, 'deleteProjectCredentials')
                );

                // Projects
                $route->post(
                    'projects/new',
                    Ref::_(ProjectsController::class, 'newProject')
                );
            });
    }
}
