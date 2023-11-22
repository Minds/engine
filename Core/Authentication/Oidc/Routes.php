<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Oidc;

use Minds\Core\Di\Ref;
use Minds\Core\Router\Route;

class Routes extends \Minds\Core\Router\ModuleRoutes
{
    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->route
            ->withPrefix('api/v3/authenticate/oidc')
            ->do(function (Route $route): void {
                $route->get(
                    'login',
                    Ref::_(Controller::class, 'oidcLogin')
                );

                $route->get(
                    'callback',
                    Ref::_(Controller::class, 'oidcCallback')
                );
            });
    }
}
