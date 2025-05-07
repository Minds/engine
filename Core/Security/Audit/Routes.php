<?php
namespace Minds\Core\Security\Audit;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Audit Routes
 * @package Minds\Core\Security\Aduit
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/security/audit')
            ->withScope(ApiScopeEnum::AUDIT_READ)
            ->withMiddleware([
                AdminMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'logs',
                    Ref::_(Controller::class, 'getLogs')
                );
                $route->get(
                    'events',
                    Ref::_(Controller::class, 'getEvents')
                );
            });
    }
}
