<?php
namespace Minds\Core\Sessions\CommonSessions;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Common sessions Routes
 * @package Minds\Core\Sessions\CommonSessions
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/sessions/common-sessions')
            ->withMiddleware([
                LoggedInMiddleware::class,
            ])
            ->do(function (Route $route) {
                $route->get(
                    'all',
                    Ref::_('Sessions\CommonSessions\Controller', 'getAll')
                );
                $route->delete(
                    'session',
                    Ref::_('Sessions\CommonSessions\Controller', 'deleteSession')
                );
                $route->delete(
                    'all',
                    Ref::_('Sessions\CommonSessions\Controller', 'deleteAllSessions')
                );
            });
    }
}
