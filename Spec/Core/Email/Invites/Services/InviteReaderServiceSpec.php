<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\Invites\Services\InviteReaderService;
use Minds\Core\Email\Invites\Types\Invite;
use Minds\Core\Email\Invites\Types\InviteConnection;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class InviteReaderServiceSpec extends ObjectBehavior
{
    private Collaborator $invitesRepositoryMock;

    public function let(
        InvitesRepository $invitesRepository
    ): void {
        $this->invitesRepositoryMock = $invitesRepository;

        $this->beConstructedWith(
            $invitesRepository
        );
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InviteReaderService::class);
    }

    public function it_should_get_invite_by_token(
        Invite $inviteMock
    ): void {
        $this->invitesRepositoryMock->getInviteByToken('token')
            ->shouldBeCalledOnce()
            ->willReturn($inviteMock);

        $this->getInviteByToken('token')
            ->shouldBe($inviteMock);
    }

    public function it_should_get_invites(): void
    {
        $this->invitesRepositoryMock->getInvites(
            10,
            0,
            false,
            null
        )
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

        $this->getInvites(
            10,
            null,
            null
        )
            ->shouldBeAnInstanceOf(InviteConnection::class);
    }

    public function it_should_get_invite_by_id(
        Invite $inviteMock
    ): void {
        $this->invitesRepositoryMock->getInviteById(1)
            ->shouldBeCalledOnce()
            ->willReturn($inviteMock);

        $this->getInviteById(1)
            ->shouldBe($inviteMock);
    }
}
