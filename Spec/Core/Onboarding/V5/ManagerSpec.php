<?php
declare(strict_types=1);

namespace Spec\Minds\Core\Onboarding\V5;

use ArrayIterator;
use Minds\Core\Entities\Actions\Save;
use Minds\Core\GraphQL\Types\KeyValuePair;
use Minds\Core\Onboarding\V5\Manager;
use Minds\Core\Onboarding\V5\Repository;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingState;
use Minds\Core\Onboarding\V5\GraphQL\Types\OnboardingStepProgressState;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    protected $repository;
    protected $save;

    public function let(Repository $repository, Save $save)
    {
        $this->repository = $repository;
        $this->save = $save;
        $this->beConstructedWith($repository, $save);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_gets_onboarding_state(User $user, OnboardingState $onboardingState)
    {
        $user->getGuid()->willReturn(123);
        $this->repository->getOnboardingState(123)->willReturn($onboardingState);

        $this->getOnboardingState($user)->shouldReturn($onboardingState);
    }

    public function it_sets_onboarding_state(User $user, OnboardingState $onboardingState)
    {
        $user->getGuid()->willReturn(123);
        $this->repository->setOnboardingState(123, true)->willReturn($onboardingState);

        $this->setOnboardingState($user, true)->shouldReturn($onboardingState);
    }

    public function it_gets_onboarding_step_progress(
        User $user,
        OnboardingStepProgressState $progressState1,
        OnboardingStepProgressState $progressState2
    ) {
        $user->getGuid()->willReturn(123);
        $this->repository->getOnboardingStepProgress(123)->willReturn(new ArrayIterator([
            $progressState1,
            $progressState2
        ]));

        $this->getOnboardingStepProgress($user)->shouldYieldLike(new \ArrayIterator([
            $progressState1,
            $progressState2
        ]));
    }

    public function it_completes_onboarding_step_without_additional_data(
        User $user,
        OnboardingStepProgressState $progressState,
    ) {
        $stepKey = 'not_onboarding_interest_survey';
        $user->getGuid()->willReturn(123);
        
        $this->repository->completeOnboardingStep(123, $stepKey, 'step_type')->willReturn($progressState);
        
        $this->completeOnboardingStep($user, $stepKey, 'step_type', [])
            ->shouldReturn($progressState);
    }

    public function it_completes_onboarding_step_with_additional_data(
        User $user,
        OnboardingStepProgressState $progressState,
    ) {
        $stepKey = 'onboarding_interest_survey';
        $additionalData = [
            new KeyValuePair('onboarding_interest', 'example')
        ];

        $this->repository->completeOnboardingStep(123, $stepKey, 'step_type')->willReturn($progressState);
        $user->getGuid()->willReturn(123);
        $user->setOnboardingInterest('example')->shouldBeCalled();
        $this->save->setEntity($user)->shouldBeCalled()->willReturn($this->save);
        $this->save->withMutatedAttributes(['onboarding_interest'])->shouldBeCalled()->willReturn($this->save);
        $this->save->save()->shouldBeCalled();

        $this->completeOnboardingStep($user, $stepKey, 'step_type', $additionalData)
            ->shouldReturn($progressState);
    }
}
