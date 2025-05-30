<?php
declare(strict_types=1);

namespace Minds\Core\Admin\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Admin\Manager;
use Minds\Core\Admin\Services\HashtagExclusionService;
use Minds\Core\Admin\Services\ModerationService;
use Minds\Core\Admin\Services\UsersService;

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

        $this->di->bind(HashtagExclusionController::class, function (Di $di): HashtagExclusionController {
            return new HashtagExclusionController(
                $di->get(HashtagExclusionService::class)
            );
        });

        $this->di->bind(UsersPsrController::class, function (Di $di): UsersPsrController {
            return new UsersPsrController(
                $di->get(UsersService::class)
            );
        });
    }
}
