<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\VerifyUniquenessNotice;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Rewards\Eligibility\Manager as EligibilityManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class VerifyUniquenessNoticeSpec extends ObjectBehavior
{
    /** @var EligibilityManager */
    protected $eligibilityManager;

    /** @var ExperimentsManager */
    protected $experimentsManager;

    public function let(
        EligibilityManager $eligibilityManager,
        ExperimentsManager $experimentsManager
    ) {
        $this->eligibilityManager = $eligibilityManager;
        $this->experimentsManager = $experimentsManager;

        $this->beConstructedWith(
            $eligibilityManager,
            $experimentsManager
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(VerifyUniquenessNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('verify-uniqueness');
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn(null);
        
        $this->eligibilityManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->eligibilityManager);

        $this->eligibilityManager->isEligible()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_when_experiment_is_off(
        User $user,
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_when_no_user_phone_hash(
        User $user,
    ) {
        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_experiment_is_off(
        User $user
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);
        
        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_is_not_eligible_for_rewards(
        User $user
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);
        
        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_already_has_a_phone_hash_set(
        User $user
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);
        
        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getPhoneNumberHash()
            ->shouldBeCalled()
            ->willReturn('');

        $this->eligibilityManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->eligibilityManager);

        $this->eligibilityManager->isEligible()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_return_instance_after_setting_user(User $user)
    {
        $this->setUser($user)
            ->shouldBe($this);
    }

    public function it_should_export(User $user)
    {
        $this->setUser($user);

        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->export()->shouldBe([
            'key' => 'verify-uniqueness',
            'location' => 'inline',
            'should_show' => false
        ]);
    }
}
