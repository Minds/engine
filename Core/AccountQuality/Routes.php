<?php

namespace Minds\Core\AccountQuality;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\AdminMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/account-quality')
            ->do(function (Route $route) {
                // Temporarily accessible only to Admins
                $route
                    ->withMiddleware([
                        AdminMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            '',
                            Ref::_('AccountQuality\Controller', 'getAccountQualityScores')
                        );
                        $route->get(
                            ':targetUserGuid',
                            Ref::_('AccountQuality\Controller', 'getAccountQualityScore')
                        );
                    });
            });
    }
}
