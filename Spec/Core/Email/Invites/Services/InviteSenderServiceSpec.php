<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\Invites\Services\InviteSenderService;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\V2\Campaigns\Recurring\Invite\InviteEmailer;
use Minds\Core\EntitiesBuilder;
use Minds\Core\MultiTenant\Services\MultiTenantBootService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;
use Prophecy\Argument;

class InviteSenderServiceSpec extends ObjectBehavior
{
    private Collaborator $invitesRepositoryMock;
    private Collaborator $entitiesBuilderMock;
    private Collaborator $multiTenantBootServiceMock;
    private Collaborator $inviteEmailerMock;

    public function let(
        InvitesRepository      $invitesRepository,
        EntitiesBuilder        $entitiesBuilder,
        MultiTenantBootService $multiTenantBootService,
        InviteEmailer          $inviteEmailer
    ): void {
        $this->invitesRepositoryMock = $invitesRepository;
        $this->entitiesBuilderMock = $entitiesBuilder;
        $this->multiTenantBootServiceMock = $multiTenantBootService;
        $this->inviteEmailerMock = $inviteEmailer;

        $this->beConstructedWith(
            $this->entitiesBuilderMock,
            $this->multiTenantBootServiceMock,
            $this->invitesRepositoryMock,
            $this->inviteEmailerMock
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InviteSenderService::class);
    }

    public function it_should_send_invites(
        User $userMock
    ): void {
        $this->invitesRepositoryMock->getInvitesToSend()
            ->shouldBeCalledOnce()
            ->willYield([
                new Invite(
                    inviteId: 1,
                    tenantId: -1,
                    ownerGuid: 1,
                    email: '',
                    inviteToken: '',
                    status: InviteEmailStatusEnum::PENDING,
                    bespokeMessage: '',
                    createdTimestamp: time(),
                )
            ]);

        $this->multiTenantBootServiceMock->bootFromTenantId(-1)
            ->shouldNotBeCalled();

        $this->entitiesBuilderMock->single(1)
            ->shouldBeCalledOnce()
            ->willReturn($userMock);

        $this->invitesRepositoryMock->updateInviteStatus(1, InviteEmailStatusEnum::SENDING)
            ->shouldBeCalledOnce();

        $this->inviteEmailerMock->setInvite(Argument::type(Invite::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->setSender($userMock)
            ->shouldBeCalledOnce();

        $this->inviteEmailerMock->send()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->invitesRepositoryMock->updateInviteStatus(1, InviteEmailStatusEnum::SENT)
            ->shouldBeCalledOnce();

        $this->sendInvites();
    }

    public function it_should_NOT_send_invites(
        User $userMock
    ): void {
        $this->invitesRepositoryMock->getInvitesToSend()
            ->shouldBeCalledOnce()
            ->willYield([
                new Invite(
                    inviteId: 1,
                    tenantId: -1,
                    ownerGuid: 1,
                    email: '',
                    inviteToken: '',
                    status: InviteEmailStatusEnum::PENDING,
                    bespokeMessage: '',
                    createdTimestamp: time(),
                )
            ]);

        $this->multiTenantBootServiceMock->bootFromTenantId(-1)
            ->shouldNotBeCalled();

        $this->entitiesBuilderMock->single(1)
            ->shouldBeCalledOnce()
            ->willReturn($userMock);

        $this->invitesRepositoryMock->updateInviteStatus(1, InviteEmailStatusEnum::SENDING)
            ->shouldBeCalledOnce();

        $this->inviteEmailerMock->setInvite(Argument::type(Invite::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->setSender($userMock)
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->send()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->invitesRepositoryMock->updateInviteStatus(1, InviteEmailStatusEnum::FAILED)
            ->shouldBeCalledOnce();

        $this->sendInvites();
    }

    public function it_should_resend_invite(
        User $userMock
    ): void {
        $this->invitesRepositoryMock->getInviteById(1)
            ->shouldBeCalledOnce()
            ->willReturn(
                new Invite(
                    inviteId: 1,
                    tenantId: -1,
                    ownerGuid: 1,
                    email: '',
                    inviteToken: '',
                    status: InviteEmailStatusEnum::FAILED,
                    bespokeMessage: '',
                    createdTimestamp: time(),
                )
            );

        $this->inviteEmailerMock->setInvite(Argument::type(Invite::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->setSender($userMock)
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->send()
            ->shouldBeCalledOnce()
            ->willReturn(true);

        $this->invitesRepositoryMock->updateInviteStatus(1, InviteEmailStatusEnum::SENT);

        $this->resendInvite(1, $userMock);
    }

    public function it_should_NOT_resend_invite(
        User $userMock
    ): void {
        $this->invitesRepositoryMock->getInviteById(1)
            ->shouldBeCalledOnce()
            ->willReturn(
                new Invite(
                    inviteId: 1,
                    tenantId: -1,
                    ownerGuid: 1,
                    email: '',
                    inviteToken: '',
                    status: InviteEmailStatusEnum::FAILED,
                    bespokeMessage: '',
                    createdTimestamp: time(),
                )
            );

        $this->inviteEmailerMock->setInvite(Argument::type(Invite::class))
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->setSender($userMock)
            ->shouldBeCalledOnce()
            ->willReturn($this->inviteEmailerMock);

        $this->inviteEmailerMock->send()
            ->shouldBeCalledOnce()
            ->willReturn(false);

        $this->invitesRepositoryMock->updateInviteStatus(1, InviteEmailStatusEnum::FAILED);

        $this->resendInvite(1, $userMock);
    }
}
