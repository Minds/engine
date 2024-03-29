<?php
/**
 * Routes
 * @author Mark
 */

namespace Minds\Core\Nostr;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Middleware\LoggedInMiddleware;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('.well-known/nostr.json')
            ->do(function (Route $route) {
                $route->get(
                    '',
                    Ref::_('Nostr\Controller', 'resolveNip05')
                );
            });
        $this->route
            ->withPrefix('api/v3/nostr')
            ->do(function (Route $route) {
                $route->get(
                    'sync',
                    Ref::_('Nostr\Controller', 'sync')
                );
                $route->get(
                    'events',
                    Ref::_('Nostr\Controller', 'getNostrEvents')
                );
                //
                $route->get(
                    'req',
                    Ref::_('Nostr\Controller', 'getReq')
                );
                $route->put(
                    'event',
                    Ref::_('Nostr\Controller', 'putEvent')
                );

                $route
                    ->withMiddleware([
                        LoggedInMiddleware::class,
                    ])
                    ->do(function (Route $route) {
                        $route->get(
                            'nip26-delegation',
                            Ref::_('Nostr\Controller', 'getNip26Delegation')
                        );
                        $route->post(
                            'nip26-delegation',
                            Ref::_('Nostr\Controller', 'setupNip26Delegation')
                        );
                        $route->delete(
                            'nip26-delegation',
                            Ref::_('Nostr\Controller', 'removeNip26Delegation')
                        );
                    });
            });
    }
}
