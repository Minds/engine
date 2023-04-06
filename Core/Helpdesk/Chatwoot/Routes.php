<?php
declare(strict_types=1);

namespace Minds\Core\Helpdesk\Chatwoot;

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
            ->withPrefix('api/v3/helpdesk/chatwoot')
            ->do(function (Route $route) {
                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'hmac',
                            Ref::_(Controller::class, 'getUserHmac')
                        );
                    });
            });
    }
}
