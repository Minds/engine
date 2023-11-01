<?php
declare(strict_types=1);

namespace Minds\Core\Admin;

use Minds\Core\Admin\Controllers\AccountsController;
use Minds\Core\Di\Di;
use Minds\Core\Di\Provider as DiProvider;
use Minds\Core\Entities\Actions\Save;

class Provider extends DiProvider
{
    public function register(): void
    {
        $this->di->bind(AccountsController::class, function (Di $di): AccountsController {
            return new AccountsController(
                manager: $di->get(Manager::class),
            );
        });

        $this->di->bind(Manager::class, function (Di $di): Manager {
            return new Manager(
                entitiesBuilder: $di->get('EntitiesBuilder'),
                entitySaveHandler: new Save(),
                totpManager: $di->get('Security\TOTP\Manager'),
                emailConfirmationManager: $di->get('Email\Confirmation'),
                spamFilter: $di->get('Email\SpamFilter'),
                emailVerifyManager: $di->get('Email\Verify\Manager'),
                logger: $di->get('Logger'),
                save: new Save(),
            );
        });
    }
}
