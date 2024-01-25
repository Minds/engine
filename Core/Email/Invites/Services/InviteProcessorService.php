<?php
declare(strict_types=1);

namespace Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use Minds\Exceptions\NotFoundException;
use Minds\Exceptions\ServerErrorException;

class InviteProcessorService
{
    public function __construct(
        private readonly InviteReaderService     $inviteReaderService,
        private readonly InviteManagementService $inviteManagementService,
        private readonly RolesService            $rolesService,
        private readonly GroupMembershipManager  $groupMembershipManager
    )
    {
    }

    /**
     * @param User $user
     * @param string $inviteToken
     * @return void
     * @throws NotFoundException
     * @throws ServerErrorException
     */
    public function processInvite(User $user, string $inviteToken): void
    {
        $invite = $this->inviteReaderService->getInviteByToken($inviteToken);

        $this->processInviteRoles($user, $invite);
        $this->processInviteGroups($user, $invite);

        $this->inviteManagementService->updateInviteStatus(
            inviteId: $invite->inviteId,
            status: InviteEmailStatusEnum::ACCEPTED
        );
    }

    /**
     * @param User $user
     * @param Invite $invite
     * @return void
     */
    private function processInviteRoles(User $user, Invite $invite): void
    {
        if (!$invite->getRoles()) {
            return;
        }

        foreach ($invite->getRoles() as $role) {
            $this->rolesService->assignUserToRole($user, $role);
        }
    }

    private function processInviteGroups(User $user, Invite $invite): void
    {
        if (!$invite->getGroups()) {
            return;
        }

        foreach ($invite->getGroups() as $groupNode) {

            $this->groupMembershipManager->joinGroup(
                group: $groupNode->getEntity(),
                user: $user,
                membershipLevel: GroupMembershipLevelEnum::MEMBER
            );
        }
    }
}
