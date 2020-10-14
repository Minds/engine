<?php

namespace Spec\Minds\Core\Onboarding\OnboardingGroups;

use Minds\Core\Onboarding\OnboardingGroups\OngoingOnboardingGroup;
use Minds\Entities\User;
use Minds\Core\Onboarding\Steps;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class OngoingOnboardingGroupSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(OngoingOnboardingGroup::class);
    }

    public function it_should_return_0_pct_completed(Steps\SuggestedChannelsStep $subscribeStep, Steps\SuggestedGroupsStep $groupsStep)
    {
        $this->beConstructedWith([ $subscribeStep, $groupsStep ]);
       
        $user = new User();

        $subscribeStep->isCompleted($user)
            ->willReturn(false);

        $groupsStep->isCompleted($user)
            ->willReturn(false);
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->getCompletedPct()->shouldBe((float) 0);
        $onboardingGroup->isCompleted()->shouldReturn(false);
    }

    public function it_should_return_50_pct_completed(Steps\SuggestedChannelsStep $subscribeStep, Steps\SuggestedGroupsStep $groupsStep)
    {
        $this->beConstructedWith([ $subscribeStep, $groupsStep ]);
       
        $user = new User();
    
        $subscribeStep->isCompleted($user)
            ->willReturn(true);

        $groupsStep->isCompleted($user)
            ->willReturn(false);
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->getCompletedPct()->shouldBe((float) 0.5);
        $onboardingGroup->isCompleted()->shouldReturn(false);
    }

    public function it_should_return_100_pct_completed(Steps\SuggestedChannelsStep $subscribeStep, Steps\SuggestedGroupsStep $groupsStep)
    {
        $this->beConstructedWith([ $subscribeStep, $groupsStep ]);
       
        $user = new User();
    
        $subscribeStep->isCompleted($user)
            ->willReturn(true);

        $groupsStep->isCompleted($user)
            ->willReturn(true);
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->getCompletedPct()->shouldBe((float) 1);
        $onboardingGroup->isCompleted()->shouldReturn(true);
    }
}
