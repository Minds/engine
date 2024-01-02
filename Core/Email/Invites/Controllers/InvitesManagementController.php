<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Controllers;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Services\InviteManagementService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

class InvitesManagementController
{
    public function __construct(
        private readonly InviteManagementService $inviteManagementService,
    ) {
    }

    /**
     * @param string $emails
     * @param string $bespokeMessage
     * @param int[]|null $roles
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
        $this->inviteManagementService->createInvite(
            user: $loggedInUser,
            emails: $emails,
            bespokeMessage: $bespokeMessage,
            roles: $roles,
            groups: $groups,
        );
    }

    /**
     * @param int $inviteId
     * @return void
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function cancelInvite(
        int $inviteId
    ): void {
        $this->inviteManagementService->updateInviteStatus(
            inviteId: $inviteId,
            status: InviteEmailStatusEnum::CANCELLED
        );
    }
}
