<?php
namespace Minds\Core\OAuth;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Oauth Routes
 * @package Minds\Core\OAuth
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/oauth')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'authorize',
                            Ref::_('OAuth\Controller', 'authorize')
                        );
                        $route->get(
                            'userinfo',
                            Ref::_('OAuth\Controller', 'userinfo')
                        );
                        $route->get(
                            'revoke',
                            Ref::_('OAuth\Controller', 'revoke')
                        );
                    });

                $route->post(
                    'token',
                    Ref::_('OAuth\Controller', 'token')
                );
                $route->get(
                    'jwks',
                    Ref::_('OAuth\Controller', 'jwks')
                );
            });

        $this->route
            ->withPrefix('.well-known/openid-configuration')
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('OAuth\Controller', 'getOpenIDConfiguration')
                );
            });
    }
}
