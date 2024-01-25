<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Services\InviteManagementService;
use Minds\Core\Email\Invites\Services\InviteProcessorService;
use Minds\Core\Email\Invites\Services\InviteReaderService;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\EntitiesBuilder;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use ReflectionClass;

class InviteProcessorServiceSpec extends ObjectBehavior
{
    private Collaborator $inviteReaderServiceMock;
    private Collaborator $inviteManagementServiceMock;
    private Collaborator $rolesServiceMock;
    private Collaborator $groupMembershipManagerMock;
    private Collaborator $entitiesBuilderMock;

    public function let(
        InviteReaderService     $inviteReaderService,
        InviteManagementService $inviteManagementService,
        RolesService            $rolesService,
        GroupMembershipManager  $groupMembershipManager,
        EntitiesBuilder         $entitiesBuilder
    ): void
    {
        $this->inviteReaderServiceMock = $inviteReaderService;
        $this->inviteManagementServiceMock = $inviteManagementService;
        $this->rolesServiceMock = $rolesService;
        $this->groupMembershipManagerMock = $groupMembershipManager;
        $this->entitiesBuilderMock = $entitiesBuilder;

        $this->beConstructedWith(
            $inviteReaderService,
            $inviteManagementService,
            $rolesService,
            $groupMembershipManager,
            $entitiesBuilder
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InviteProcessorService::class);
    }

    public function it_process_invite_successfully_with_NO_roles_NO_groups(
        User $user
    ): void
    {
        $inviteFactory = new ReflectionClass(Invite::class);
        $inviteMock = $inviteFactory->newInstanceWithoutConstructor();
        $inviteFactory->getProperty('inviteId')->setValue($inviteMock, 1);
        $inviteFactory->getProperty('roles')->setValue($inviteMock, null);
        $inviteFactory->getProperty('groups')->setValue($inviteMock, null);

        $this->inviteReaderServiceMock->getInviteByToken('token')
            ->shouldBeCalledOnce()
            ->willReturn(
                $inviteMock
            );

        $this->inviteManagementServiceMock->updateInviteStatus(
            inviteId: 1,
            status: InviteEmailStatusEnum::ACCEPTED
        )->shouldBeCalledOnce();

        $this->processInvite($user, 'token');
    }
}
