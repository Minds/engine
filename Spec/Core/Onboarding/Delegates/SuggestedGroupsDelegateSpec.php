<?php

namespace Spec\Minds\Core\Onboarding\Delegates;

use Minds\Core\Onboarding\Delegates\SuggestedGroupsDelegate;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SuggestedGroupsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SuggestedGroupsDelegate::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $user->getGroupMembership()
            ->shouldBeCalled()
            ->willReturn([2000, 2001]);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $user->getGroupMembership()
            ->shouldBeCalled()
            ->willReturn([]);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
