<?php
declare(strict_types=1);

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\Channels\AvatarService;
use Minds\Core\Log\Logger;
use Minds\Core\Entities\Actions\Save as SaveAction;
use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserAvatarDelegate;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class UpdateUserAvatarDelegateSpec extends ObjectBehavior
{
    private Collaborator $avatarServiceMock;
    private Collaborator $loggerMock;
    private Collaborator $saveActionMock;
    private Collaborator $aclMock;

    public function let(
        AvatarService $avatarServiceMock,
        Logger $loggerMock,
        SaveAction $saveActionMock,
        ACL $aclMock
    ) {
        $this->avatarServiceMock = $avatarServiceMock;
        $this->loggerMock = $loggerMock;
        $this->saveActionMock = $saveActionMock;
        $this->aclMock = $aclMock;

        $this->beConstructedWith(
            $avatarServiceMock,
            $loggerMock,
            $saveActionMock,
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

        $this->saveActionMock->setEntity($userMock)
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);
        
        $this->saveActionMock->withMutatedAttributes(['icontime'])
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->saveActionMock->save()
            ->shouldBeCalled()
            ->willReturn($this->saveActionMock);

        $this->onUpdate($userMock, $blob)
            ->shouldReturn(true);
    }
}
