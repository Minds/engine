<?php
namespace Minds\Core\Helpdesk\Zendesk;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;
use Minds\Core\Router\Middleware\LoggedInMiddleware;

/**
 * Zendesk Routes
 * @package Minds\Core\Helpdesk\Zendesk
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/helpdesk/zendesk')
            ->do(function (Route $route) {
                // Logged in endpoints
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            '',
                            Ref::_('Helpdesk\Zendesk\Controller', 'redirect')
                        );
                    });
            });
    }
}
