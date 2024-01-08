<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Email\Invites\Services;

use Minds\Core\Email\Invites\Enums\InviteEmailStatusEnum;
use Minds\Core\Email\Invites\Repositories\InvitesRepository;
use Minds\Core\Email\Invites\Services\InviteManagementService;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class InviteManagementServiceSpec extends ObjectBehavior
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
        $this->shouldBeAnInstanceOf(InviteManagementService::class);
    }

    public function it_should_create_invite(
        User $userMock
    ): void {
        $this->invitesRepositoryMock->createInvite(
            user: $userMock,
            emails: ['email'],
            bespokeMessage: 'message',
            roles: null,
            groups: null
        )
            ->shouldBeCalledOnce();

        $this->createInvite(
            $userMock,
            'email',
            'message',
            null,
            null
        );
    }

    public function it_should_update_invite_status(): void
    {
        $this->invitesRepositoryMock->updateInviteStatus(
            1,
            InviteEmailStatusEnum::SENT
        )
            ->shouldBeCalledOnce();

        $this->updateInviteStatus(
            1,
            InviteEmailStatusEnum::SENT
        );
    }
}
