<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Admin\Manager;
use Minds\Core\Admin\Services\ModerationService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
      
        $this->di->bind(AccountsController::class, function (Di $di): AccountsController {
            return new AccountsController(
                manager: $di->get(Manager::class),
            );
        });

        $this->di->bind(ModerationController::class, function (Di $di): ModerationController {
            return new ModerationController(
                $di->get(ModerationService::class)
            );
        });
    }
}
