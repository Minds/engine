<?php

namespace Spec\Minds\Core\Channels\Delegates;

use Minds\Core\Channels\Delegates\Logout;
use Minds\Core\Sessions\CommonSessions;
use Minds\Entities\User;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class LogoutSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Logout::class);
    }

    public function it_should_logout(CommonSessions\Manager $sessions)
    {
        $this->beConstructedWith($sessions);
        $user = new User();
        $user->guid = 123;

        $sessions->deleteAll($user)
            ->shouldBeCalled();

        $this->logout($user);
    }
}
