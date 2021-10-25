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
                    ->do(function (Route $route) {
                        $route->get(
                            'questions',
                            Ref::_('SocialCompass\Controller', 'getQuestions')
                        );
                    });
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->post(
                            'answers',
                            Ref::_('SocialCompass\Controller', 'storeAnswers')
                        );
                        $route->put(
                            'answers',
                            Ref::_('SocialCompass\Controller', 'updateAnswers')
                        );
                    });
            });
    }
}
