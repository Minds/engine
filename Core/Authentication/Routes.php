<?php

declare(strict_types=1);

namespace Minds\Core\Authentication;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\Route;

class Routes extends \Minds\Core\Router\ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/authenticate')
            ->do(function (Route $route): void {
                $route->post(
                    '',
                    Ref::_('Authentication\Controller', 'authenticate')
                );

                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function (Route $route): void {
                        $route->delete(
                            '',
                            Ref::_('Authentication\Controller', 'deleteSession')
                        );
                        $route->delete(
                            'all',
                            Ref::_('Authentication\Controller', 'deleteAllUserSessions')
                        );
                    });
            });
    }
}
