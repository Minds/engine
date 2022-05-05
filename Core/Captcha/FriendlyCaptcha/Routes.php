<?php
namespace Minds\Core\Captcha\FriendlyCaptcha;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * FriendlyCaptcha routes.
 */
class Routes extends ModuleRoutes
{
    /**
     * Registers all module routes
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/friendly-captcha')
            ->do(function (Route $route) {
                $route->get(
                    'puzzle',
                    Ref::_('FriendlyCaptcha\Controller', 'generatePuzzle')
                );
                $route->post(
                    'verify',
                    Ref::_('FriendlyCaptcha\Controller', 'verifySolution')
                );
            });
    }
}
