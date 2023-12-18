<?php
declare(strict_types=1);

namespace Minds\Core\MultiTenant\Controllers;

use Minds\Core\MultiTenant\Services\InvitesService;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;
use TheCodingMachine\GraphQLite\Annotations\InjectUser;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Security;

class InvitesController
{
    public function __construct(
        private readonly InvitesService $invitesService,
    ) {
    }

    /**
     * @param string[] $emails
     * @param string $bespokeMessage
     * @param string[]|null $roles
     * @param int[]|null $groups
     * @return void
     * @throws ServerErrorException
     */
    #[Mutation]
    #[Logged]
    #[Security("is_granted('ROLE_ADMIN', loggedInUser)")]
    public function invite(
        array              $emails,
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
}
