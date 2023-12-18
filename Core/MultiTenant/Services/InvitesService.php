<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Services;

use Minds\Core\MultiTenant\Repositories\InvitesRepository;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class InvitesService
{
    public function __construct(
        private readonly InvitesRepository $invitesRepository,
    ) {
    }

    /**
     * @param User $user
     * @param array $emails
     * @param string $bespokeMessage
     * @param array|null $roles
     * @param array|null $groups
     * @return void
     * @throws ServerErrorException
     */
    public function createInvite(
        User   $user,
        array  $emails,
        string $bespokeMessage,
        ?array $roles = null,
        ?array $groups = null,
    ): void {
        // TODO: Validate emails, roles

        $this->invitesRepository->createInvite(
            user: $user,
            emails: $emails,
            bespokeMessage: $bespokeMessage,
            roles: $roles,
            groups: $groups,
        );
    }
}
