<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\FeedNotices\Notices\ConnectWalletNotice;
use Minds\Core\FeedNotices\Notices\VerifyUniquenessNotice;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ConnectWalletNoticeSpec extends ObjectBehavior
{
    /** @var VerifyUniquenessNotice */
    protected $verifyUniquenessNotice;

    /** @var ExperimentsManager */
    protected $experimentsManager;

    public function let(
        VerifyUniquenessNotice $verifyUniquenessNotice,
        ExperimentsManager $experimentsManager
    ) {
        $this->verifyUniquenessNotice = $verifyUniquenessNotice;
        $this->experimentsManager = $experimentsManager;

        $this->beConstructedWith(
            $verifyUniquenessNotice,
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
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->verifyUniquenessNotice->meetsPrerequisites($user)
            ->shouldBeCalled()
            ->willReturn(true);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
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

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_has_eth_wallet(
        User $user,
    ) {
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verifyUniquenessNotice->meetsPrerequisites($user)
            ->shouldNotBeCalled()
            ->willReturn(true);

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_has_not_met_prerequisites(
        User $user,
    ) {
        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn(null);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verifyUniquenessNotice->meetsPrerequisites($user)
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
            ->willReturn(null);

        $this->experimentsManager->isOn('minds-3131-onboarding-notices')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->verifyUniquenessNotice->meetsPrerequisites($user)
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
