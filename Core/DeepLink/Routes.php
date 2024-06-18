<?php
declare(strict_types=1);

namespace Minds\Core\DeepLink;

use Minds\Core\DeepLink\Controllers\WellKnownPsrController;
use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('/.well-known')
            ->do(function (Route $route) {
                $route->get(
                    '/apple-app-site-association',
                    Ref::_(WellKnownPsrController::class, 'getAppleAppSiteAssosciations')
                );
                $route->get(
                    '/assetlinks.json',
                    Ref::_(WellKnownPsrController::class, 'getAndroidAppLinks')
                );
            });
    }
}
