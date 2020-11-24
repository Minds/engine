<?php

namespace Spec\Minds\Core\Onboarding\Steps;

use Minds\Core\Onboarding\Steps\VerifyUniquenessStep;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class VerifyUniquenessStepSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(VerifyUniquenessStep::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('0303456');

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
