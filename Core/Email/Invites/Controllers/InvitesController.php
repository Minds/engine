<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Controllers;

use Minds\Core\Email\Invites\Services\InvitesService;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Invites\Types\InviteConnection;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Annotations\Security;

class InvitesController
{
    public function __construct(
        private readonly InvitesService $invitesService,
    ) {
    }

    /**
     * @param string $emails
     * @param string $bespokeMessage
     * @param RolesEnum[]|null $roles
     * @param int[]|null $groups
     * @return void
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function invite(
        string             $emails,
        string             $bespokeMessage,
        #[InjectUser] User $loggedInUser,
        ?array             $roles = null,
        ?array             $groups = null,
    ): void {
        $this->invitesService->createInvite(
            user: $loggedInUser,
            emails: $emails,
            bespokeMessage: $bespokeMessage,
            roles: $roles,
            groups: $groups,
        );
    }

    /**
     * @param string $inviteToken
     * @return Invite
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    #[Query]
    public function getInvite(
        string $inviteToken
    ): Invite {
        return $this->invitesService->getInviteByToken($inviteToken);
    }

    /**
     * @param int $first
     * @param string|null $after
     * @param string|null $search
     * @return InviteConnection
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
        return $this->invitesService->getInvites(
            first: $first,
            after: $after,
            search: $search,
        );
    }
}
