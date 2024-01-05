<?php

namespace Spec\Minds\Core\FeedNotices\Notices;

use Minds\Core\Config\Config;
use Minds\Core\FeedNotices\Notices\ConnectWalletNotice;
use Minds\Core\Rewards\Eligibility\Manager as EligibilityManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;
use PhpSpec\Wrapper\Collaborator;

class ConnectWalletNoticeSpec extends ObjectBehavior
{
    /** @var EligibilityManager */
    protected $eligibilityManager;
    
    /** @var Config */
    private $config;

    public function let(
        EligibilityManager $eligibilityManager,
        Config $config
    ) {
        $this->eligibilityManager = $eligibilityManager;
        $this->config = $config;

        $this->beConstructedWith(
            $eligibilityManager,
            $config
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
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);
    
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
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

        $user->getEthWallet()
            ->shouldBeCalled()
            ->willReturn('123');

        $this->callOnWrappedObject('shouldShow', [$user])
            ->shouldBe(false);
    }

    public function it_should_determine_if_notice_should_NOT_show_because_user_is_not_eligible_for_rewards(
        User $user,
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn(null);

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

    public function it_should_determine_if_notice_should_NOT_show_because_this_is_a_tenant_context(
        User $user
    ) {
        $this->config->get('tenant_id')
            ->shouldBeCalled()
            ->willReturn('123');

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
