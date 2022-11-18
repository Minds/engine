<?php

declare(strict_types=1);

namespace Minds\Core\Twitter;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\FeatureFlagMiddleware;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/twitter')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->get(
                    'request-oauth-token',
                    Ref::_('Twitter\Controller', 'requestTwitterOAuthToken')
                );

                $route->get(
                    'redirect-oauth-token',
                    Ref::_('Twitter\Controller', 'redirectToTwitterAuthUrl')
                );

                $route->get(
                    'oauth-callback',
                    Ref::_('Twitter\Controller', 'generateTwitterOAuthAccessToken')
                );

                $route
                    ->withMiddleware([
                        [
                            'class' => FeatureFlagMiddleware::class,
                            'args' => [
                                'twitter-v2-integration'
                            ]
                        ]
                    ])
                    ->do(function (Route $route) {
                        $route->post(
                            'tweets',
                            Ref::_('Twitter\Controller', 'postTweet')
                        );
                        $route->get(
                            'config',
                            Ref::_('Twitter\Controller', 'getUserConfig')
                        );
                    });
            });
    }
}
