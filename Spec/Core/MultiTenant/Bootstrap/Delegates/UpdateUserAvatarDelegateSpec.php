<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Channels\AvatarService;
use Minds\Core\Log\Logger;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserAvatarDelegate;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class UpdateUserAvatarDelegateSpec extends ObjectBehavior
{
    private Collaborator $avatarServiceMock;
    private Collaborator $loggerMock;
    private Collaborator $aclMock;

    public function let(
        AvatarService $avatarServiceMock,
        Logger $loggerMock,
        ACL $aclMock
    ) {
        $this->avatarServiceMock = $avatarServiceMock;
        $this->loggerMock = $loggerMock;
        $this->aclMock = $aclMock;

        $this->beConstructedWith(
            $avatarServiceMock,
            $loggerMock,
            $aclMock
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdateUserAvatarDelegate::class);
    }

    public function it_should_update_user_avatar(User $userMock): void
    {
        $blob = 'fake-blob';

        $this->avatarServiceMock->withUser($userMock)
            ->shouldBeCalled()
            ->willReturn($this->avatarServiceMock);
        
        $this->avatarServiceMock->createFromBlob($blob)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->onUpdate($userMock, $blob)
            ->shouldReturn(true);
    }
}
