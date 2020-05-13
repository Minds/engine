<?php
namespace Minds\Core\Wire\SupportTiers;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Wire Support Tiers routes handler
 * @package Minds\Core\Wire\SupportTiers
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/wire/supporttiers')
            ->do(function (Route $route) {
                $route->get(
                    '/:urn',
                    Ref::_('Wire\SupportTiers\Controller', 'getSingle')
                );

                $route->get(
                    '/all/:guid',
                    Ref::_('Wire\SupportTiers\Controller', 'getAll')
                );

                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            '/',
                            Ref::_('Wire\SupportTiers\Controller', 'getAll')
                        );

                        $route->post(
                            '/',
                            Ref::_('Wire\SupportTiers\Controller', 'create')
                        );

                        $route->post(
                            '/:urn',
                            Ref::_('Wire\SupportTiers\Controller', 'update')
                        );

                        $route->delete(
                            '/:urn',
                            Ref::_('Wire\SupportTiers\Controller', 'delete')
                        );
                    });
            });
    }
}
