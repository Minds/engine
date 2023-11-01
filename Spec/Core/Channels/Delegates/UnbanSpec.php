<?php

namespace Spec\Minds\Core\Channels\Delegates;

use Minds\Core\Channels\Delegates\Unban;
use Minds\Core\Entities\Actions\Save;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class UnbanSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(Unban::class);
    }

    public function it_should_ban(User $user, Save $saveMock)
    {
        $this->beConstructedWith($saveMock);

        $user->set('ban_reason', '')
            ->shouldBeCalled();

        $user->set('banned', 'no')
            ->shouldBeCalled();
            
        $saveMock->setEntity($user)->willReturn($saveMock);
        $saveMock->withMutatedAttributes(['ban_reason', 'banned'])->willReturn($saveMock);
        $saveMock->save()->shouldBeCalled()->willReturn(true);

        $this
            ->unban($user, false)
            ->shouldReturn(true);
    }
}
