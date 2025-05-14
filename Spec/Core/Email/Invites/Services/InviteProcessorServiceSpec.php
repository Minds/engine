<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\Invites\Services;

use Minds\Common\SystemUser;
use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Services\InviteManagementService;
use Minds\Core\Email\Invites\Services\InviteProcessorService;
use Minds\Core\Email\Invites\Services\InviteReaderService;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Groups\V2\GraphQL\Types\GroupNode;
use Minds\Core\Groups\V2\Membership\Enums\GroupMembershipLevelEnum;
use Minds\Core\Groups\V2\Membership\Manager as GroupMembershipManager;
use Minds\Core\Security\Rbac\Enums\RolesEnum;
use Minds\Core\Security\Rbac\Models\Role;
use Minds\Core\Security\Rbac\Services\RolesService;
use Minds\Entities\Group;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;
use ReflectionClass;

class InviteProcessorServiceSpec extends ObjectBehavior
{
    private Collaborator $inviteReaderServiceMock;
    private Collaborator $inviteManagementServiceMock;
    private Collaborator $rolesServiceMock;
    private Collaborator $groupMembershipManagerMock;

    public function let(
        InviteReaderService     $inviteReaderService,
        InviteManagementService $inviteManagementService,
        RolesService            $rolesService,
        GroupMembershipManager  $groupMembershipManager,
    ): void {
        $this->inviteReaderServiceMock = $inviteReaderService;
        $this->inviteManagementServiceMock = $inviteManagementService;
        $this->rolesServiceMock = $rolesService;
        $this->groupMembershipManagerMock = $groupMembershipManager;

        $this->beConstructedWith(
            $inviteReaderService,
            $inviteManagementService,
            $rolesService,
            $groupMembershipManager
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InviteProcessorService::class);
    }

    public function it_process_invite_successfully_with_NO_roles_NO_groups(
        User $user
    ): void {
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

    public function it_process_invite_successfully_with_WITH_roles_NO_groups(
        User $user
    ): void {
        $inviteFactory = new ReflectionClass(Invite::class);
        $inviteMock = $inviteFactory->newInstanceWithoutConstructor();
        $inviteFactory->getProperty('inviteId')->setValue($inviteMock, 1);
        $inviteFactory->getProperty('roles')->setValue($inviteMock, [1, 2]);
        $inviteFactory->getProperty('groups')->setValue($inviteMock, null);

        $this->inviteReaderServiceMock->getInviteByToken('token')
            ->shouldBeCalledOnce()
            ->willReturn(
                $inviteMock
            );

        $this->rolesServiceMock->assignUserToRole(
            user: $user,
            role: new Role(
                id: RolesEnum::from(1)->value,
                name: RolesEnum::from(1)->name,
                permissions: []
            ),
            adminUser: Argument::type(SystemUser::class),
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->rolesServiceMock->assignUserToRole(
            user: $user,
            role: new Role(
                id: RolesEnum::from(2)->value,
                name: RolesEnum::from(2)->name,
                permissions: []
            ),
            adminUser: Argument::type(SystemUser::class),
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->inviteManagementServiceMock->updateInviteStatus(
            inviteId: 1,
            status: InviteEmailStatusEnum::ACCEPTED
        )->shouldBeCalledOnce();

        $this->processInvite($user, 'token');
    }

    public function it_process_invite_successfully_with_NO_roles_WITH_groups(
        User      $userMock,
        Group     $groupMock,
        GroupNode $groupNodeMock
    ): void {
        $inviteFactory = new ReflectionClass(Invite::class);
        $inviteMock = $inviteFactory->newInstanceWithoutConstructor();
        $inviteFactory->getProperty('inviteId')->setValue($inviteMock, 1);
        $inviteFactory->getProperty('roles')->setValue($inviteMock, null);
        $inviteFactory->getProperty('groups')->setValue($inviteMock, [
            new GroupNode($groupMock->getWrappedObject())
        ]);

        $this->inviteReaderServiceMock->getInviteByToken('token')
            ->shouldBeCalledOnce()
            ->willReturn(
                $inviteMock
            );

        $this->groupMembershipManagerMock->joinGroup(
            $groupMock->getWrappedObject(),
            $userMock,
            GroupMembershipLevelEnum::MEMBER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);


        $this->inviteManagementServiceMock->updateInviteStatus(
            inviteId: 1,
            status: InviteEmailStatusEnum::ACCEPTED
        )->shouldBeCalledOnce();

        $this->processInvite($userMock, 'token');
    }

    public function it_process_invite_successfully_with_WITH_roles_WITH_groups(
        User      $userMock,
        Group     $groupMock,
        GroupNode $groupNodeMock
    ): void {
        $inviteFactory = new ReflectionClass(Invite::class);
        $inviteMock = $inviteFactory->newInstanceWithoutConstructor();
        $inviteFactory->getProperty('inviteId')->setValue($inviteMock, 1);
        $inviteFactory->getProperty('roles')->setValue($inviteMock, [1, 2]);
        $inviteFactory->getProperty('groups')->setValue($inviteMock, [
            new GroupNode($groupMock->getWrappedObject())
        ]);

        $this->inviteReaderServiceMock->getInviteByToken('token')
            ->shouldBeCalledOnce()
            ->willReturn(
                $inviteMock
            );

        $this->rolesServiceMock->assignUserToRole(
            user: $userMock,
            role: new Role(
                id: RolesEnum::from(1)->value,
                name: RolesEnum::from(1)->name,
                permissions: []
            ),
            adminUser: Argument::type(SystemUser::class),
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->rolesServiceMock->assignUserToRole(
            user: $userMock,
            role: new Role(
                id: RolesEnum::from(2)->value,
                name: RolesEnum::from(2)->name,
                permissions: []
            ),
            adminUser: Argument::type(SystemUser::class),
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->groupMembershipManagerMock->joinGroup(
            $groupMock->getWrappedObject(),
            $userMock,
            GroupMembershipLevelEnum::MEMBER
        )
            ->shouldBeCalledOnce()
            ->willReturn(true);


        $this->inviteManagementServiceMock->updateInviteStatus(
            inviteId: 1,
            status: InviteEmailStatusEnum::ACCEPTED
        )->shouldBeCalledOnce();

        $this->processInvite($userMock, 'token');
    }
}
