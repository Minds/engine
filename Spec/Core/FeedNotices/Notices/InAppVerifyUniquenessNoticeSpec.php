<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\FeedNotices\Notices\InAppVerifyUniquenessNotice;
use Minds\Core\Rewards\Eligibility\Manager as EligibilityManager;
use Minds\Core\Verification\Manager as VerificationManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class InAppVerifyUniquenessNoticeSpec extends ObjectBehavior
{
    /** @var EligibilityManager */
    protected $eligibilityManager;

    /** @var ExperimentsManager */
    protected $experimentsManager;

    /** @var VerificationManager */
    protected $verificationManager;

    public function let(
        EligibilityManager $eligibilityManager,
        ExperimentsManager $experimentsManager,
        VerificationManager $verificationManager
    ) {
        $this->eligibilityManager = $eligibilityManager;
        $this->experimentsManager = $experimentsManager;
        $this->verificationManager = $verificationManager;
        $this->beConstructedWith($eligibilityManager, $experimentsManager, $verificationManager);
    }

    public function it_is_initializable(): void
    {
        $this->shouldBeAnInstanceOf(InAppVerifyUniquenessNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('top');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('verify-uniqueness');
    }

    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-275-in-app-verification')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verificationManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->verificationManager);

        $this->verificationManager->isVerified()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_the_experiment_is_off(
        User $user
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-275-in-app-verification')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_the_user_is_already_verified(
        User $user
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-275-in-app-verification')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verificationManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->verificationManager);

        $this->verificationManager->isVerified()
            ->shouldBeCalled()
            ->willReturn(true);

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
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('epic-275-in-app-verification')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verificationManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->verificationManager);

        $this->verificationManager->isVerified()
            ->shouldBeCalled()
            ->willReturn(false);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'verify-uniqueness',
            'location' => 'top',
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
