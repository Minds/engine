<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Controllers;

use Minds\Core\Email\Invites\Services\InviteSenderService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

class InvitesSenderController
{
    public function __construct(
        private readonly InviteSenderService $inviteSenderService,
    ) {
    }

    /**
     * @param int $inviteId
     * @return void
     * @throws ServerErrorException
     * @throws NotFoundException
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function resendInvite(
        int                $inviteId,
        #[InjectUser] User $loggedInUser,
    ): void {
        $this->inviteSenderService->resendInvite(
            inviteId: $inviteId,
            sender: $loggedInUser,
        );
    }
}
