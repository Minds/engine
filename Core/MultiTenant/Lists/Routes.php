<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Lists;

use Minds\Core\Di\Ref;
use Minds\Core\MultiTenant\Lists\Controllers\ChannelsListPsrController;
use Minds\Core\MultiTenant\Lists\Controllers\GroupsListPsrController;
use Minds\Core\Router\ModuleRoutes;
use Minds\Core\Router\Route;

class Routes extends ModuleRoutes
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->route->withPrefix('api/v3/multi-tenant/lists')
            ->do(function (Route $route): void {
                $route->get(
                    'user',
                    Ref::_(ChannelsListPsrController::class, 'getChannels')
                );
                $route->get(
                    'group',
                    Ref::_(GroupsListPsrController::class, 'getGroups')
                );
            });
    }
}
