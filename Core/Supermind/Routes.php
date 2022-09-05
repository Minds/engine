<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/supermind')
            ->withMiddleware([
                LoggedInMiddleware::class
            ])
            ->do(function (Route $route) {
                $route->get(
                    'inbox',
                    Ref::_('Supermind\Controller', 'getSupermindInboxRequests')
                );
                $route->get(
                    'outbox',
                    Ref::_('Supermind\Controller', 'getSupermindOutboxRequests')
                );
                $route->post(
                    ':guid/reject',
                    Ref::_('Supermind\Controller', 'rejectSupermindRequest')
                );
                $route
                    ->withMiddleware([
                        AdminMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->delete(
                            ':guid',
                            Ref::_('Supermind\Controller', 'revokeSupermindRequest')
                        );
                    });
            });
    }
}
