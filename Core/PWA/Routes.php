<?php
declare(strict_types=1);

namespace Minds\Core\PWA;

use Minds\Core\Di\Ref;
use Minds\Core\PWA\Controllers\ManifestController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * PWA Routes
 * @package Minds\Core\PWA
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/pwa')
            ->do(function (Route $route) {
                $route->get(
                    'manifest',
                    Ref::_(ManifestController::class, 'getManifest')
                );
            });
    }
}
