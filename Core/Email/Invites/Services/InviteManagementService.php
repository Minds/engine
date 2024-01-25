<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Entities\User;
use Minds\Exceptions\ServerErrorException;

class InviteManagementService
{
    public function __construct(
        private readonly InvitesRepository $invitesRepository
    ) {
    }

    /**
     * @param User $user
     * @param string $emails
     * @param string $bespokeMessage
     * @param RolesEnum[]|null $roles
     * @param array|null $groups
     * @return void
     * @throws ServerErrorException
     */
    public function createInvite(
        User   $user,
        string $emails,
        string $bespokeMessage,
        ?array $roles = null,
        ?array $groups = null,
    ): void {
        $this->invitesRepository->createInvite(
            user: $user,
            emails: $this->prepareEmailAddresses($emails),
            bespokeMessage: $bespokeMessage,
            roles: $roles,
            groups: $groups,
        );
    }

    /**
     * Processes a list of emails in string format and returns a list of unique email addresses
     * Note: Allowed separators are comma and newline
     * @param string $emails
     * @return array
     */
    private function prepareEmailAddresses(string $emails): array
    {
        return array_unique(
            array_merge(
                ...array_values(
                    array_map(
                        fn (string $email) => array_filter(
                            explode(
                                "\n",
                                $email
                            ),
                            fn (string $email) => !empty($email),
                        ),
                        explode(',', str_replace(" ", "", $emails))
                    )
                )
            )
        );
    }

    /**
     * @param int $inviteId
     * @param InviteEmailStatusEnum $status
     * @return bool
     */
    public function updateInviteStatus(int $inviteId, InviteEmailStatusEnum $status): bool
    {
        return $this->invitesRepository->updateInviteStatus($inviteId, $status);
    }
}
