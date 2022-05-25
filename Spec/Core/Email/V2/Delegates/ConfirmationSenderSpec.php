<?php

namespace Spec\Minds\Core\Email\V2\Delegates;

use Minds\Core\Email\V2\Delegates\ConfirmationSender;
use Minds\Core\Email\V2\Campaigns\Recurring\Confirmation\Confirmation as ConfirmationEmail;
use Minds\Core\Experiments\Manager as ExperimentsManager;
use Minds\Entities\User;
use PhpSpec\ObjectBehavior;

class ConfirmationSenderSpec extends ObjectBehavior
{
    /** @var ConfirmationEmail */
    private $confirmation;

    /** @var ExperimentsManager */
    private $experiments;

    public function let(ConfirmationEmail $confirmation, ExperimentsManager $experiments)
    {
        $this->beConstructedWith($confirmation, $experiments);
        $this->confirmation = $confirmation;
        $this->experiments = $experiments;
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(ConfirmationSender::class);
    }

    public function it_should_send_if_code_experiment_is_off(
        User $user
    ) {
        $this->experiments->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experiments);
        
        $this->experiments->isOn('minds-3055-email-codes')
            ->shouldBeCalled()
            ->willReturn(false);

        $this->confirmation->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->confirmation);
        
        $this->confirmation->send()
            ->shouldBeCalled();

        $this->send($user);
    }

    public function it_should_NOT_send_if_code_experiment_is_on(
        User $user
    ) {
        $this->experiments->setUser($user)
            ->shouldBeCalled()
            ->willReturn($this->experiments);
        
        $this->experiments->isOn('minds-3055-email-codes')
            ->shouldBeCalled()
            ->willReturn(true);

        $this->confirmation->setUser($user)
            ->shouldNotBeCalled();
        
        $this->confirmation->send()
            ->shouldNotBeCalled();

        $this->send($user);
    }
}
