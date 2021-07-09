<?php
namespace Minds\Core\Register;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Register Routes
 * @package Minds\Core\Register
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/register')
            ->do(function (Route $route) {
                $route->get(
                    'validate',
                    Ref::_('Register\Controller', 'validate')
                );
            });
    }
}
