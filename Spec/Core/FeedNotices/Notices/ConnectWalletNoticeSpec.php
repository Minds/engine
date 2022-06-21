<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\ConnectWalletNotice;
use Minds\Core\FeedNotices\Notices\VerifyUniquenessNotice;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Core\Rewards\Eligibility\Manager as EligibilityManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ConnectWalletNoticeSpec extends ObjectBehavior
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
        $this->shouldHaveType(ConnectWalletNotice::class);
    }

    public function it_should_get_location()
    {
        $this->getLocation()->shouldBe('inline');
    }

    public function it_should_get_key()
    {
        $this->getKey()->shouldBe('connect-wallet');
    }

    public function it_should_determine_if_notice_should_show(
        User $user,
    ) {
        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->eligibilityManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->eligibilityManager);

        $this->eligibilityManager->isEligible()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_experiment_is_off(
        User $user,
    ) {
        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_has_no_eth_wallet(
        User $user,
    ) {
        $user->getEthWallet()
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

    public function it_should_determine_if_notice_should_NOT_show_because_user_is_not_eligible_for_rewards(
        User $user,
    ) {
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn();

        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

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
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn();

        $this->experimentsManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experimentsManager);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->eligibilityManager->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->eligibilityManager);

        $this->eligibilityManager->isEligible()
            ->shouldBeCalled()
            ->willReturn(true);

        $this->setUser($user);

        $this->export()->shouldBe([
            'key' => 'connect-wallet',
            'location' => 'inline',
            'should_show' => true
        ]);
    }
}
