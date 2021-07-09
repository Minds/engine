<?php

namespace Spec\Minds\Core\Security\TOTP\Delegates;

use Minds\Core\Security\TOTP\Delegates\EmailDelegate;
use Minds\Core\Di\Di;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Minds\Core\Email\V2\Campaigns\Custom\Custom;
use Minds\Entities\User;

class EmailDelegateSpec extends ObjectBehavior
{
    /** @var Custom */
    private $campaign;

    public function let(Custom $campaign)
    {
        $this->beConstructedWith($campaign);
        $this->campaign = $campaign;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(EmailDelegate::class);
    }

    public function it_should_send_on_recovery()
    {
        $user = new User();

        $this->campaign->setUser($user)
            ->shouldBeCalled();

        $this->campaign->setTemplate('totp-recovery-code-used')
            ->shouldBeCalled();

        $this->campaign->setSubject('2FA disabled')
            ->shouldBeCalled();

        $this->campaign->setTitle('2FA disabled')
            ->shouldBeCalled();

        $this->campaign->setPreheader('Two-factor security has been disabled. ')
            ->shouldBeCalled();

        $this->campaign->send()
            ->shouldBeCalled();

        $this->onRecover($user);
    }
}
