<?php

namespace Minds\Core\SocialCompass;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{

    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/social-compass')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function(Route $route) {
                        $route->get(
                            'questions',
                            Ref::_('SocialCompass\Controller', 'getQuestions')
                        );
                    });
            });
        // TODO: Implement register() method.
    }
}
