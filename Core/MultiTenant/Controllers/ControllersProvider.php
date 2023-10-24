<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\MultiTenant\Services\NetworksService;
use Minds\Core\MultiTenant\Services\NetworkUsersService;
use Minds\Core\MultiTenant\Services\FeaturedEntityService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(NetworksController::class, function (Di $di): NetworksController {
            return new NetworksController(
                $di->get(NetworksService::class)
            );
        });
        $this->di->bind(NetworkUsersController::class, function (Di $di): NetworkUsersController {
            return new NetworkUsersController(
                $di->get(NetworkUsersService::class)
            );
        });
        $this->di->bind(FeaturedEntitiesController::class, function (Di $di): FeaturedEntitiesController {
            return new FeaturedEntitiesController(
                $di->get(FeaturedEntityService::class)
            );
        });
    }
}
