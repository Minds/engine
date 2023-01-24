<?php
declare(strict_types=1);

namespace Minds\Core\Verification;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/verification')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route): void {
                $route->get(
                    ':deviceid',
                    Ref::_('Verification\Controller', 'getVerificationStatus')
                );

                $route->post(
                    ':deviceid',
                    Ref::_('Verification\Controller', 'generateVerificationCode')
                );

                $route->post(
                    ':deviceid/verify',
                    Ref::_('Verification\Controller', 'verifyAccount')
                );
            });
    }
}
