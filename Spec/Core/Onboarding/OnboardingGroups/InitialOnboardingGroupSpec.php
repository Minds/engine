<?php

namespace Spec\Minds\Core\Onboarding\OnboardingGroups;

use Minds\Core\Onboarding\OnboardingGroups\InitialOnboardingGroup;
use Minds\Entities\User;
use Minds\Core\Onboarding\Steps;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

class InitialOnboardingGroupSpec extends ObjectBehavior
{
    public function it_is_initializable()
    {
        $this->shouldHaveType(InitialOnboardingGroup::class);
    }

    public function it_should_return_50_pct_completed(Steps\SuggestedHashtagsStep $tagsStep)
    {
        $this->beConstructedWith([ new Steps\VerifyEmailStep(), $tagsStep ]);
       

        $user = new User();
        $user->email_confirmed_at = time();

        $tagsStep->isCompleted($user)
            ->willReturn(false);
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->getCompletedPct()->shouldBe(0.5);
    }

    public function it_should_return_0_pct_completed(Steps\SuggestedHashtagsStep $tagsStep)
    {
        $this->beConstructedWith([ new Steps\VerifyEmailStep(), $tagsStep ]);
       
        $user = new User();

        $tagsStep->isCompleted($user)
            ->willReturn(false);
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->getCompletedPct()->shouldBe((float) 0);
    }

    public function it_should_return_100_pct_completed(Steps\SuggestedHashtagsStep $tagsStep)
    {
        $this->beConstructedWith([ new Steps\VerifyEmailStep(), $tagsStep ]);
       
        $user = new User();
        $user->email_confirmed_at = time();

        $tagsStep->isCompleted($user)
            ->willReturn(true);
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->getCompletedPct()->shouldBe((float) 1);
    }

    public function it_should_return_is_completed_false()
    {
        $user = new User();
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->isCompleted()->shouldBe(false);
    }

    public function it_should_return_is_completed_true()
    {
        $user = new User();
        $user->initial_onboarding_completed = time();
       
        //

        $onboardingGroup = $this->setUser($user);
        $onboardingGroup->isCompleted()->shouldBe(true);
    }

    public function it_should_export_onboarding_group()
    {
        $this->beConstructedWith([ new Steps\VerifyEmailStep() ]);
        
        $user = new User();
        $user->initial_onboarding_completed = time();
       
        //

        $onboardingGroup = $this->setUser($user);
        $export = $onboardingGroup->export();
        $export['is_completed']->shouldBe(true);
    }
}
