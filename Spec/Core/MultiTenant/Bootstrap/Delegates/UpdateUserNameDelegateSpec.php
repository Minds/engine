<?php

namespace Spec\Minds\Core\MultiTenant\Bootstrap\Delegates;

use Minds\Core\MultiTenant\Bootstrap\Delegates\UpdateUserNameDelegate;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\Log\Logger;
use Minds\Core\Security\ACL;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class UpdateUserNameDelegateSpec extends ObjectBehavior
{
    private Collaborator $saveActionMock;
    private Collaborator $loggerMock;
    private Collaborator $aclMock;

    public function let(Save $saveActionMock, Logger $loggerMock, ACL $aclMock)
    {
        $this->saveActionMock = $saveActionMock;
        $this->loggerMock = $loggerMock;
        $this->aclMock = $aclMock;

        $this->beConstructedWith($saveActionMock, $loggerMock, $aclMock);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(UpdateUserNameDelegate::class);
    }

    public function it_should_update_user_name(User $user)
    {
        $name = 'Username2';
        $userGuid = 1234567890;

        $user->getGuid()->willReturn($userGuid);
        $user->setName($name)->shouldBeCalled();

        $this->saveActionMock->setEntity($user)->willReturn($this->saveActionMock);
        $this->saveActionMock->save(true)->shouldBeCalled();

        $this->onUpdate($user, $name);
    }
}
