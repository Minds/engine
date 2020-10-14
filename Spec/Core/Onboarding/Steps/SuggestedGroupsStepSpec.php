<?php

namespace Spec\Minds\Core\Onboarding\Steps;

use Minds\Core\Onboarding\Steps\SuggestedGroupsStep;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SuggestedGroupsStepSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SuggestedGroupsStep::class);
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
