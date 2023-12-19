<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Di\Di;
use Minds\Core\Di\ImmutableException;
use Minds\Core\Di\Provider;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;

class ServicesProvider extends Provider
{
    /**
     * @return void
     * @throws ImmutableException
     */
    public function register(): void
    {
        $this->di->bind(
            InvitesService::class,
            fn (Di $di): InvitesService => new InvitesService(
                invitesRepository: $di->get(InvitesRepository::class),
            )
        );
    }
}
