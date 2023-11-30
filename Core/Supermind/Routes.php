<?php

declare(strict_types=1);

namespace Minds\Core\Supermind;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\Middleware\MauticWebhookMiddleware;
use Minds\Core\Router\Middleware\NotMultiTenantMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/supermind')
            ->withMiddleware([
                MauticWebhookMiddleware::class,
                NotMultiTenantMiddleware::class,
            ])
            ->do(function (Route $route): void {
                $route->post(
                    'bulk',
                    Ref::_('Supermind\Controller', 'createBulkSupermindRequest')
                );
            });
        $this->route
            ->withPrefix('api/v3/supermind')
            ->withMiddleware([
                LoggedInMiddleware::class,
                NotMultiTenantMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'inbox',
                    Ref::_('Supermind\Controller', 'getSupermindInboxRequests')
                );
                $route->get(
                    'inbox/count',
                    Ref::_('Supermind\Controller', 'countSupermindInboxRequests')
                );
                $route->get(
                    'outbox',
                    Ref::_('Supermind\Controller', 'getSupermindOutboxRequests')
                );
                $route->get(
                    'outbox/count',
                    Ref::_('Supermind\Controller', 'countSupermindOutboxRequests')
                );
                $route->get(
                    ':guid',
                    Ref::_('Supermind\Controller', 'getSupermindRequest')
                );
                $route->post(
                    ':guid/reject',
                    Ref::_('Supermind\Controller', 'rejectSupermindRequest')
                );
                $route->post(
                    ':guid/accept-live',
                    Ref::_('Supermind\Controller', 'acceptLiveSupermindRequest')
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
