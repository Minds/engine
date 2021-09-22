<?php

namespace Spec\Minds\Core\Onboarding\Steps;

use Minds\Core\Onboarding\Steps\VerifyEmailStep;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VerifyEmailStepSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(VerifyEmailStep::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $user->isTrusted()
            ->willReturn(true);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $user->isTrusted()
            ->willReturn(false);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
