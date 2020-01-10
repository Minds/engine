<?php

namespace Spec\Minds\Core\Onboarding\Delegates;

use Minds\Core\Onboarding\Delegates\DisplayNameDelegate;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class DisplayNameDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(DisplayNameDelegate::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('phpspec');

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $user->get('name')
            ->shouldBeCalled()
            ->willReturn('');

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
