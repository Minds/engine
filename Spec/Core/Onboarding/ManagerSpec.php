<?php

namespace Spec\Minds\Core\Onboarding;

use Minds\Core\Config;
use Minds\Core\Features\Manager as FeaturesManager;
use Minds\Core\Onboarding\Steps\OnboardingStepInterface;
use Minds\Core\Onboarding\OnboardingGroups;
use Minds\Core\Onboarding\Manager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ManagerSpec extends ObjectBehavior
{
    /** @var OnboardingDelegate[] */
    protected $delegates;

    /** @var FeaturesManager */
    protected $features;

    /** @var Config */
    protected $config;

    public function let(
        OnboardingStepInterface $onboardingDelegate1,
        OnboardingStepInterface $onboardingDelegate2,
        OnboardingStepInterface $onboardingDelegate3,
        FeaturesManager $features,
        Config $config
    ) {
        $this->delegates = [
            'delegate1' => $onboardingDelegate1,
            'delegate2' => $onboardingDelegate2,
            'delegate3' => $onboardingDelegate3,
        ];

        $this->features = $features;

        $this->config = $config;

        $this->beConstructedWith($this->delegates, $this->features, $this->config);
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(Manager::class);
    }

    public function it_should_check_if_was_onboarding_shown(User $user)
    {
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(900);

        $user->wasOnboardingShown()
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this
            ->setUser($user)
            ->wasOnboardingShown()
            ->shouldReturn(true);
    }

    public function it_should_check_if_user_is_older_than_feature(User $user)
    {
        $this->config->get('onboarding_modal_timestamp')
            ->shouldBeCalled()
            ->willReturn(900);

        $user->wasOnboardingShown()
            ->shouldNotBeCalled();

        $user->getTimeCreated()
            ->shouldBeCalled()
            ->willReturn(800);

        $this
            ->setUser($user)
            ->wasOnboardingShown()
            ->shouldReturn(true);
    }

    public function it_should_set_onboarding_shown(User $user)
    {
        $user->setOnboardingShown(true)
            ->shouldBeCalled()
            ->willReturn($user);

        $user->save()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this
            ->setUser($user)
            ->setOnboardingShown(true)
            ->shouldReturn(true);
    }

    public function it_should_get_creator_frequency(User $user)
    {
        $user->getCreatorFrequency()
            ->shouldBeCalled()
            ->willReturn('rarely');

        $this
            ->setUser($user)
            ->getCreatorFrequency()
            ->shouldReturn('rarely');
    }

    public function it_should_return_initial_onboarding(OnboardingGroups\InitialOnboardingGroup $initialOnboardingGroup)
    {
        $this->beConstructedWith(null, null, null, $initialOnboardingGroup);

        $user = new User();

        $initialOnboardingGroup->setUser($user)
            ->willReturn($initialOnboardingGroup);

        $initialOnboardingGroup->isCompleted()
            ->willReturn(false);

        $initialOnboardingGroup->getCompletedPct()
            ->willReturn(0.2);

        //

        $this->setUser($user);
        $this->getOnboardingGroup()
            ->shouldBe($initialOnboardingGroup);
    }

    public function it_should_return_ongoing_onboarding(OnboardingGroups\InitialOnboardingGroup $initialOnboardingGroup, OnboardingGroups\OngoingOnboardingGroup $ongoingOnboardingGroup)
    {
        $this->beConstructedWith(null, null, null, $initialOnboardingGroup, $ongoingOnboardingGroup);
    
        $user = new User();

        $initialOnboardingGroup->setUser($user)
            ->willReturn($initialOnboardingGroup);

        $initialOnboardingGroup->isCompleted()
            ->willReturn(true);

        $ongoingOnboardingGroup->setUser($user)
            ->willReturn($ongoingOnboardingGroup);

        //

        $this->setUser($user);
        $this->getOnboardingGroup()
            ->shouldBe($ongoingOnboardingGroup);
    }

    public function it_should_set_initial_onboarding_as_complete(
        User $user,
        OnboardingGroups\InitialOnboardingGroup $initialOnboardingGroup,
        OnboardingGroups\OngoingOnboardingGroup $ongoingOnboardingGroup
    ) {
        $this->beConstructedWith(null, null, null, $initialOnboardingGroup, $ongoingOnboardingGroup);

        $user->setInitialOnboardingCompleted(time())
            ->willReturn($user);

        $user->save()
            ->shouldBeCalled();

        $initialOnboardingGroup->setUser($user)
            ->willReturn($initialOnboardingGroup);

        $initialOnboardingGroup->isCompleted()
            ->willReturn(false);

        $initialOnboardingGroup->getCompletedPct()
            ->willReturn(1);

        $ongoingOnboardingGroup->setUser($user)
            ->willReturn($ongoingOnboardingGroup);

        //

        $this->setUser($user);
        $this->getOnboardingGroup()
            ->shouldBe($ongoingOnboardingGroup);
    }

    // Legacy steps (will be removed soon --- OCT 2020 - MH)

    public function it_should_set_creator_frequency(User $user)
    {
        $user->setCreatorFrequency('rarely')
            ->shouldBeCalled()
            ->willReturn($user);

        $user->save()
            ->shouldBeCalled()
            ->willReturn(1000);

        $this
            ->setUser($user)
            ->setCreatorFrequency('rarely')
            ->shouldReturn(true);
    }

    public function it_should_get_all_items()
    {
        $this
            ->getAllItems()
            ->shouldReturn([
                'delegate1',
                'delegate2',
                'delegate3',
            ]);
    }

    public function it_should_get_completed_items(User $user)
    {
        $this->delegates['delegate1']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delegates['delegate2']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->delegates['delegate3']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this
            ->setUser($user)
            ->getCompletedItems()
            ->shouldReturn([
                'delegate1',
                'delegate3',
            ]);
    }

    public function it_should_mark_a_user_complete(User $user)
    {
        $this
            ->setUser($user)
            ->getAllItems()
            ->shouldReturn([
                'delegate1',
                'delegate2',
                'delegate3',
            ]);

        $this->delegates['delegate1']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delegates['delegate2']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delegates['delegate3']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->isComplete()->shouldReturn(true);
    }

    public function it_should_mark_a_user_incomplete(User $user)
    {
        $this
            ->setUser($user)
            ->getAllItems()
            ->shouldReturn([
                'delegate1',
                'delegate2',
                'delegate3',
            ]);

        $this->delegates['delegate1']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delegates['delegate2']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->delegates['delegate3']
            ->isCompleted($user)
            ->shouldBeCalled()
            ->willReturn(false);

        $this->isComplete()->shouldReturn(false);
    }
}
