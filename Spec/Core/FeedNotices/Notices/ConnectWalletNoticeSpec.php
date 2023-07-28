<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\ConnectWalletNotice;
use Minds\Core\Rewards\Eligibility\Manager as EligibilityManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ConnectWalletNoticeSpec extends ObjectBehavior
{
    /** @var EligibilityManager */
    protected $eligibilityManager;

    public function let(
        EligibilityManager $eligibilityManager,
    ) {
        $this->eligibilityManager = $eligibilityManager;

        $this->beConstructedWith(
            $eligibilityManager,
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


    public function it_should_get_whether_notice_is_dismissible()
    {
        $this->isDismissible()->shouldBe(true);
    }

    public function it_should_determine_if_notice_should_show(
        User $user,
    ) {
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

    public function it_should_determine_if_notice_should_NOT_show_because_user_has_no_eth_wallet(
        User $user,
    ) {
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_is_not_eligible_for_rewards(
        User $user,
    ) {
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn();

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
            'should_show' => true,
            'is_dismissible' => true
        ]);
    }
}
