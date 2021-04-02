<?php
namespace Minds\Core\Security\Password;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Password Routes
 * @package Minds\Core\Security\Password
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/security/password')
            ->do(function (Route $route) {
                $route->get(
                    'risk',
                    Ref::_('Security\Password\Controller', 'getRisk')
                );
            });
    }
}
