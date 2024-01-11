<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\V2\Campaigns\Recurring\Invite\InviteEmailer;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            InviteManagementService::class,
            fn (Di $di): InviteManagementService => new InviteManagementService(
                invitesRepository: $di->get(InvitesRepository::class),
            )
        );
        $this->di->bind(
            InviteReaderService::class,
            fn (Di $di): InviteReaderService => new InviteReaderService(
                invitesRepository: $di->get(InvitesRepository::class),
            )
        );
        $this->di->bind(
            InviteSenderService::class,
            fn (Di $di): InviteSenderService => new InviteSenderService(
                entitiesBuilder: $di->get('EntitiesBuilder'),
                multiTenantBootService: $di->get(MultiTenantBootService::class),
                invitesRepository: $di->get(InvitesRepository::class),
                inviteEmailer: new InviteEmailer(),
            )
        );
    }
}
