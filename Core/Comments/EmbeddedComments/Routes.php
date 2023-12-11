<?php
namespace Minds\Core\Comments\EmbeddedComments;

use Minds\Core\Comments\EmbeddedComments\Controllers\EmbeddedCommentsPsrController;
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
            ->withPrefix('api/v3/embedded-comments')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                       
                        $route->get(
                            'auth/success',
                            Ref::_(EmbeddedCommentsPsrController::class, 'closeWindow')
                        );
                    });
            });
    }
}
