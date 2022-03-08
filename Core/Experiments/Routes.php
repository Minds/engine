<?php
namespace Minds\Core\Experiments;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

/**
 * Experiments Routes
 * @package Minds\Core\Experiments
 */
class Routes extends ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/experiments')
            ->do(function (Route $route) {
                $route->get(
                    ':id',
                    Ref::_('Experiments\Controller', 'isOn')
                );
            });
    }
}
