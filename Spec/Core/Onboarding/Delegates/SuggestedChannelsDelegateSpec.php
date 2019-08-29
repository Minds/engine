<?php

namespace Spec\Minds\Core\Onboarding\Delegates;

use Minds\Core\Onboarding\Delegates\SuggestedChannelsDelegate;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class SuggestedChannelsDelegateSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(SuggestedChannelsDelegate::class);
    }

    public function it_should_check_if_completed(User $user)
    {
        $user->getSubscriptonsCount()
            ->shouldBeCalled()
            ->willReturn(500);

        $this
            ->isCompleted($user)
            ->shouldReturn(true);
    }

    public function it_should_check_if_not_completed(User $user)
    {
        $user->getSubscriptonsCount()
            ->shouldBeCalled()
            ->willReturn(1);

        $this
            ->isCompleted($user)
            ->shouldReturn(false);
    }
}
