<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Controllers;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Email\Invites\Services\InviteManagementService;
use Minds\Core\Email\Invites\Services\InviteReaderService;
use Minds\Core\Email\Invites\Services\InviteSenderService;

class ControllersProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            InvitesReaderController::class,
            fn (Di $di): InvitesReaderController => new InvitesReaderController(
                inviteReaderService: $di->get(InviteReaderService::class),
            )
        );
        $this->di->bind(
            InvitesManagementController::class,
            fn (Di $di): InvitesManagementController => new InvitesManagementController(
                inviteManagementService: $di->get(InviteManagementService::class),
            )
        );
        $this->di->bind(
            InvitesSenderController::class,
            fn (Di $di): InvitesSenderController => new InvitesSenderController(
                inviteSenderService: $di->get(InviteSenderService::class),
            )
        );
    }
}
