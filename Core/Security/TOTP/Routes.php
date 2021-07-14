<?php
namespace Minds\Core\Security\TOTP;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * TOTP Routes
 * @package Minds\Core\Security\TOTP
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/security/totp')
            ->do(function (Route $route) {
                $route->post(
                    'recovery',
                    Ref::_('Security\TOTP\Controller', 'verifyAndRecover')
                );

                // Logged in endpoints
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'new',
                            Ref::_('Security\TOTP\Controller', 'createNewSecret')
                        );
                        $route->post(
                            'new',
                            Ref::_('Security\TOTP\Controller', 'authenticateDevice')
                        );
                        $route->delete(
                            '',
                            Ref::_('Security\TOTP\Controller', 'deleteSecret')
                        );
                    });
            });
    }
}
