<?php
namespace Minds\Core\Payments\SiteMemberships\PaywalledEntities;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/payments/site-memberships/paywalled-entities')
            ->do(function (Route $route) {
                $route->get(
                    'thumbnail/:guid',
                    Ref::_(Controllers\PaywalledEntitiesPsrController::class, 'getThumbnail')
                );
            });
    }
}
