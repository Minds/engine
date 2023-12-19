<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Email\Invites\Services\InvitesService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            InvitesController::class,
            fn (Di $di): InvitesController => new InvitesController(
                invitesService: $di->get(InvitesService::class),
            )
        );
    }
}
