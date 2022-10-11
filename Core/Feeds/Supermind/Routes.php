<?php

declare(strict_types=1);

namespace Minds\Core\Feeds\Supermind;

use Minds\Core\Di\Ref;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/newsfeed/superminds')
            ->do(function (Route $route): void {
                $route->get(
                    '',
                    Ref::_('Feeds\Superminds\Controller', 'getFeed')
                );
            });
    }
}
