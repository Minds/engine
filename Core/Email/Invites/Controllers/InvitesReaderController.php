<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Controllers;

use Minds\Core\Email\Invites\Services\InviteReaderService;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Invites\Types\InviteConnection;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class InvitesReaderController
{
    public function __construct(
        private readonly InviteReaderService $inviteReaderService,
    ) {
    }

    /**
     * @param int $inviteId
     * @return Invite
     */
    #[Query]
    public function getInvite(
        int $inviteId
    ): Invite {
        return $this->inviteReaderService->getInviteById($inviteId);
    }

    /**
     * @param int $first
     * @param string|null $after
     * @param string|null $search
     * @return InviteConnection
     * @throws ServerErrorException
     */
    #[Query]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function getInvites(
        #[InjectUser] User $loggedInUser,
        int                $first,
        ?string            $after = null,
        ?string            $search = null,
    ): InviteConnection {
        return $this->inviteReaderService->getInvites(
            first: $first,
            after: $after,
            search: $search,
        );
    }
}
