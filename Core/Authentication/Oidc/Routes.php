<?php

declare(strict_types=1);

namespace Minds\Core\Authentication\Oidc;

use Minds\Core\Authentication\Oidc\Controllers\OidcPsr7Controller;
use Minds\Core\Di\Ref;
use Minds\Core\Router\Enums\ApiScopeEnum;
use Minds\Core\Router\Middleware\AdminMiddleware;
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
                    Ref::_(OidcPsr7Controller::class, 'oidcLogin')
                );

                $route->get(
                    'callback',
                    Ref::_(OidcPsr7Controller::class, 'oidcCallback')
                );

                $route
                    ->withMiddleware([
                        AdminMiddleware::class,
                    ])
                    ->withScope(ApiScopeEnum::OIDC_MANAGE_USERS)
                    ->post(
                        'suspend/:sub',
                        Ref::_(OidcPsr7Controller::class, 'suspendUser')
                    );
            });
    }
}
