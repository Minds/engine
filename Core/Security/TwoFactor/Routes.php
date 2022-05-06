<?php
namespace Minds\Core\Security\TwoFactor;

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
            ->withPrefix('api/v3/two-factor')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->post(
                            'confirm-email',
                            Ref::_('Security\TwoFactor\Controller', 'confirmEmail')
                        );
                    });
            });
    }
}
